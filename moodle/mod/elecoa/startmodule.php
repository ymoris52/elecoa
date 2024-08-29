<?php
require_once(dirname(__FILE__) . '/core/init_www.php');

$debug = FALSE;
if ($debug) {
    register_shutdown_function(function() {
        $e = error_get_last();
        if ($e['type'] == E_ERROR || $e['type'] == E_PARSE || $e['type'] == E_CORE_ERROR || $e['type'] == E_COMPILE_ERROR || $e['type'] == E_USER_ERROR) {
            echo "Fatal Error occured.<br>";
            echo "Error type:\t {$e['type']}<br>";
            echo "Error message:\t {$e['message']}<br>";
            echo "Error file:\t {$e['file']}<br>";
            echo "Error line:\t {$e['line']}<br>";
        }
    });
}

// パラメータの取得
$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$e  = optional_param('e', 0, PARAM_INT);  // elecoa instance ID - it should be named as the first character of the module
$mode = optional_param('mode', 'resume', PARAM_TEXT);
$toc = optional_param('toc', 0, PARAM_INT);

// コースモジュール・コース情報の取得
if ($id) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_coursemodule_id($id);
}
elseif ($e) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_instance_id($e);
}
else {
    elecoa_print_error('invalidstartmoduleparam');
}

// ログインチェック
require_login($course, TRUE, $cm);

$event = \mod_elecoa\event\course_module_started::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $elecoa);
$event->trigger();

// マニフェストファイルの取得
if (!isset($elecoa->cmid)) {
    $elecoa->cmid = $cm->id;
}
//$context = get_context_instance(CONTEXT_MODULE, $elecoa->cmid);
$context = context_module::instance($elecoa->cmid);

$fs = get_file_storage();
$manifest = $fs->get_file($context->id, elecoa_get_module_name(), 'content', 0, '/', 'elecoa.xml');
if (!$manifest) {
    elecoa_print_error('incorrectpackage');
}

// 基本コンテキストの作成
$uid = $USER->id;
$cid = $elecoa->id;
$resume = FALSE;
$attempt_number = 1;

$log = getLogModule();
$lastattempt = $log->getLastAttempt($uid, $cid);
if ($lastattempt) {
    $resume_data_exists = $log->existsResumeData($uid, $cid, $lastattempt);
    if ($resume_data_exists && $mode === 'resume') {
        // resume
        $resume = TRUE;
        $attempt_number = $lastattempt;
    } else {
        // new
        $attempt_number = $lastattempt + 1;
    }
}

$elecoa_context = makeContext($uid, $cid, $attempt_number);

// 学習データ記録の準備
$doc = new DOMDocument();
if (!$doc->loadXML($manifest->get_content())) {
    elecoa_print_error('incorrectmanifest');
}

$manifestNode = $doc->documentElement;

$activity_root_node = selectSingleDOMNode($manifestNode, 'item');
if (is_null($activity_root_node)) {
    elecoa_print_error('incorrectmanifest');
}
$sgo = !($activity_root_node->getAttribute('oGS') === 'false'); // adlseq:objectivesGlobalToSystem
$ownwindow = ($activity_root_node->getAttribute('ownwindow') === 'true');

if (!($log->makeLogReady($uid, $cid, $attempt_number, $sgo))) {
    elecoa_print_error('couldnotmakelogready');
}

// 学習目標のインスタンス化
$objectives = array();
$objective_root_node = selectSingleDOMNode($manifestNode, 'objectives');
if (!is_null($objective_root_node)) {
    foreach (selectDOMNodes($objective_root_node, 'objective') as $objective_node) {
        $objective_node_id = $objective_node->getAttribute('id');
        $objective_node_cotype = $objective_node->getAttribute('coType');
        $objectives[$objective_node_id] = new $objective_node_cotype($elecoa_context, $objective_node_id, $objective_node, $resume, $sgo);
    }
}

// 階層構造のインスタンス化
$activities = array();
$activity_root_node_cotype = $activity_root_node->getAttribute('coType');
if ($activity_root_node_cotype === '') {
    elecoa_print_error('incorrectmanifest');
}
$maketree = function($node, $parent_node_index) use (&$maketree, &$activities, &$objectives, $resume, &$elecoa_context) {
    foreach (selectDOMNodes($node, 'item') as $child_node) {
        $child_node_cotype = $child_node->getAttribute('coType');
        $activities[] = new $child_node_cotype($elecoa_context, $parent_node_index, $child_node, $resume, $objectives);
        $child_node_index = count($activities) - 1;
        $activities[$parent_node_index]->addChild($child_node_index);
        if ($activities[$child_node_index]->getType() === 'BLOCK') {
            $maketree($child_node, $child_node_index);
        }
    }
};

$activities[] = new $activity_root_node_cotype($elecoa_context, -1, $activity_root_node, $resume, $objectives);
$maketree($activity_root_node, 0);

colib::dynamicAppend($elecoa_context, $activities, $objectives);

Platform::createInstance($activities, $objectives);

// 配信ノードの決定
// この直後 startmodule.js から READY コマンドが送られるが、その時にノードが決まっていないといけないため、このタイミングで実行
// mainmodule.php の中で READY コマンドの時に実行するでも良いかもしれない
if ($resume) {
    $current_node_id = $log->getCurrentIDForResumption($uid, $cid, $attempt_number);
    if ($current_node_id === FALSE) {
        elecoa_print_error('error');
    }
} else {
    $current_node_id = $activity_root_node->getAttribute('startID');
}

if ($current_node_id === '') {
    $results = $activities[0]->exeStart();
    if(isset($results['NextID']))
        $current_node_id = $results['NextID'];
    else
        $current_node_id = "";

} else {
    $current_node_index = find_activity_by_id($activities, $current_node_id);
    if ($current_node_index === FALSE) {
        elecoa_print_error('error');
    }
    if ($activities[$current_node_index]->getType() === 'BLOCK') {
        $results = $activities[$current_node_index]->exeStart();
        $current_node_id = $results['NextID'];
    }
}

// セッションにデータを保持
elecoa_session_set_data($elecoa->id, $activities, $current_node_id, $objectives, $elecoa_context);
elecoa_session_set_user($uid, $USER->lastname . ' ' . $USER->firstname);

// 出力（処理はstartmodule.jsで行なう）
if ($toc == 0) {
    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="en">' . "\n";
    echo ' <head>' . "\n";
    echo '  <meta charset="utf-8">' . "\n";
    echo '  <title></title>' . "\n";
    echo '  <script>' . "\n";
    echo '   document.write(\'<base href="' . preg_replace('/startmodule.php.*$/', 'startmodule.php', $_SERVER['REQUEST_URI']) . '">\');' . "\n";
    echo '   document.write(\'<script>var elecoa_id = ', $id, ', content_id = ', $cid, ($ownwindow ? ', ownwindow = true' : ''), ';<\/script>\');' . "\n";
    echo '   document.write(\'<script src="./js/core.js"><\/script>\');' . "\n";
    echo '   document.write(\'<script src="./js/startmodule.js"><\/script>\');' . "\n";
    echo '  </script>' . "\n";
    echo ' </head>' . "\n";
    echo ' <body>' . "\n";
    echo ' </body>' . "\n";
    echo '</html>' . "\n";
} else {
    $cmid = $elecoa->cmid;
    $number_of_activities = count($activities);
    $pos = '';
    for ($i = 0; $i < $number_of_activities; $i++) {
        if ($current_node_id === $activities[$i]->getID()) {
            $pos = $i;
            break;
        }
    }
    if ($pos === '') {
        for ($i = 0; $i < $number_of_activities; $i++) {
            if ($activities[$i]->getType() === 'LEAF') {
                $pos = $i;
                break;
            }
        }
    }

    $commandEntry = new CommandEntry($activities[$pos]);
    $indexResult = $commandEntry->callCommand('INDEX', NULL);

    $PAGE->set_url('/mod/'. elecoa_get_module_path_name() .'/view.php', array('id' => $cm->id));
    $PAGE->set_title($elecoa->name);
    $PAGE->set_heading($course->shortname);
    $PAGE->set_cacheable(FALSE);
    $PAGE->set_pagelayout('frametop');
    $CFG->additionalhtmlhead .= '<link rel="stylesheet" href="./css/default.css" />';
    echo $OUTPUT->header();
    echo '<script>';
    echo 'fetch("./setlaunchmode.php?lm=0", {credentials: "include"});';
    echo 'function setlaunchmode(launchMode) {';
    echo     'fetch("./setlaunchmode.php?lm=" + launchMode, {credentials: "include"});';
    echo '}';
    echo '</script>';
    echo '<div class="elecoa-choices">';
    echo '<input type="radio" id="lm-normal" name="launchmode" value="0" onclick="javascript:setlaunchmode(0);" checked/><label for="lm-normal">Normal</label>';
    echo '<input type="radio" id="lm-browse" name="launchmode" value="1" onclick="javascript:setlaunchmode(1);"/><label for="lm-browse">Browse</label>';
    echo '<input type="radio" id="lm-review" name="launchmode" value="2" onclick="javascript:setlaunchmode(2);"/><label for="lm-browse">Review</label>';
    echo '</div>';
    if ($toc == 0) {
        echo '<div style="text-align:center;margin:10px;"><a class="elecoa-nav-button" href="./ownwindow.php?id=' . $cmid . '&amp;NextID=' . $current_node_id . '">開始</a></div>';
    }
    echo '<div style="text-align:center;width:50%;min-width:300px;max-width:500px;margin:auto;margin-top:10px;">';
    echo '<div style="text-align:left;border:solid 1px #e3e3e3;background-color:#f5f5f5;padding:8px;border-radius:4px;">';
    $maketree = function($node) use (&$maketree, $cmid, $toc) {
        $id = $node['id'];
        $title = $node['title'];
        $type = $node['type'];
        echo '<li style="list-style:none;">';
        if ($type === 'LEAF') {
            if ($node['sufficientlyCompleted']) {
                echo '<img src="images/leaf_completed_passed.png" /> ';
            } else {
                echo '<img src="images/leaf_unknown_unknown.png" /> ';
            }
            if ($toc == 0) {
                echo '<span>' . $title . '</span><br>';
            } else {
                echo '<a href="./choice.php?cmid=' . $cmid . '&itemid=' . $id . '">' . $title . '</a><br>';
            }
        } else {
            $children = $node['children'];
            if ($node['sufficientlyCompleted']) {
                echo '<img src="images/block_completed_passed.png" /> ';
            } else {
                echo '<img src="images/block_unknown_unknown.png" /> ';
            }
            echo '<span>' . $title . '</span><br>';
            echo '<ul>';
            foreach ($children as $child) {
                $maketree($child);
            }
            echo '</ul>';
        }
        echo '</li>';
    };
    $maketree($indexResult['Value']);
    echo '</div>';
    echo '<div style="margin:10px;"><a class="elecoa-nav-button" href="./suspend.php?cmid=' . $cmid . '">終了</a></div>';
    echo '</div>';
    echo $OUTPUT->footer();
}
