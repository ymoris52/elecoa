<?php
require_once dirname(__FILE__) . '/core/init_www.php';

$cmid = optional_param('cmid', 0, PARAM_INT); // coursemodule.id

if ($cmid) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_coursemodule_id($cmid);
} else {
    error('You must specify a coursemodule id');
}

if (FALSE) {
    register_shutdown_function(function() {
        $e = error_get_last();
        if ($e['type'] == E_ERROR || $e['type'] == E_PARSE || $e['type'] == E_CORE_ERROR || $e['type'] == E_COMPILE_ERROR || $e['type'] == E_USER_ERROR) {
            echo "Fatal Error occured.\n";
            echo "Error type:\t {$e['type']}\n";
            echo "Error message:\t {$e['message']}\n";
            echo "Error file:\t {$e['file']}\n";
            echo "Error line:\t {$e['line']}\n";
        }
    });
}

require_login($course, TRUE, $cm);

$activities = elecoa_session_get_activities($elecoa->id);
$objectives = elecoa_session_get_objectives($elecoa->id);
Platform::createInstance($activities, $objectives);

$context = context_module::instance($cm->id);
$fs = get_file_storage();
$manifest = $fs->get_file($context->id, elecoa_get_module_name(), 'content', 0, '/', 'elecoa.xml');

if (!$manifest) {
    elecoa_print_error('incorrectmanifest');
}
$doc = new DOMDocument();
if (!$doc->loadXML($manifest->get_content())) {
    elecoa_print_error('incorrectmanifest');
}
$root_node = selectSingleNode($doc->documentElement, 'item');
if (is_null($root_node)) {
    elecoa_print_error('incorrectmanifest');
}
$showtoc = ($root_node->getAttribute('showtoc') === 'true');

$context = context_module::instance($cm->id);
$current = elecoa_session_get_currentid($elecoa->id);
$number_of_activities = count($activities);
$pos = '';
for ($i = 0; $i < $number_of_activities; $i++) {
    if ($current === $activities[$i]->getID()) {
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
if ($pos === '' or $pos === $number_of_activities) {
    elecoa_ajax_error();
}
if (!$showtoc) {
    echo '<!DOCTYPE html>' . "\n";
    echo '<html>' . "\n";
    echo '<head>' . "\n";
    echo '<script>' . "\n";
    echo 'document.write(\'<base href="' . preg_replace('/return.php.*$/', 'return.php', $_SERVER['REQUEST_URI']) . '">\');' . "\n";
    echo 'document.write(\'<script>var elecoa_id = ', $cmid, ', content_id = ', $elecoa->id, ', ownwindow = true;<\/script>\');' . "\n";
    echo 'document.write(\'<script>var baseUrl = "', $CFG->wwwroot, '", cid = ', $course->id, ';<\/script>\');' . "\n";
    echo 'document.write(\'<script src="./js/core.js"><\/script>\');' . "\n";
    echo 'document.write(\'<script src="./js/continue.js"><\/script>\');' . "\n";
    echo '</script>' . "\n";
    echo '</head>' . "\n";
    echo '<body></body>' . "\n";
    echo '</html>' . "\n";
} else {
    $commandEntry = new CommandEntry($activities[$pos]);
    $commandEntry->callCommand('EXIT', NULL);
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
    echo '<div style="text-align:center;width:50%;min-width:300px;max-width:500px;margin:auto;margin-top:10px;">';
    echo '<div style="text-align:left;border:solid 1px #e3e3e3;background-color:#f5f5f5;padding:8px;border-radius:4px;">';
    $maketree = function($node) use (&$maketree, $cmid) {
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
            echo '<a href="./choice.php?cmid=' . $cmid . '&itemid=' . $id . '">' . $title . '</a><br>';
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
