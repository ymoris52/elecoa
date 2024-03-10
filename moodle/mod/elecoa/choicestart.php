<?php
require_once dirname(__FILE__) . '/core/init_www.php';

$cmid = optional_param('cmid', 0, PARAM_INT); // coursemodule.id
$itemid = optional_param('itemid', 0, PARAM_ALPHANUMEXT);

if ($cmid) {
    list($cm, $course, $elecoa) = elecoa_get_courses_array_from_coursemodule_id($cmid);
} else {
    error('You must specify a coursemodule id');
}

require_login($course, TRUE, $cm);

$context = context_module::instance($cmid);

$uid = $USER->id;
$cid = $elecoa->id;
$resume = FALSE;
$log = getLogModule();
$attempt_number = 1;

$log = getLogModule();
$lastattempt = $log->getLastAttempt($uid, $cid);
if ($lastattempt) {
    $attempt_number = $lastattempt + 1;
}

$elecoa_context = makeContext($uid, $cid, $attempt_number);

$fs = get_file_storage();
$manifest = $fs->get_file($context->id, elecoa_get_module_name(), 'content', 0, '/', 'elecoa.xml');
if (!$manifest) {
    elecoa_print_error('incorrectpackage');
}

// 学習データ記録の準備
$doc = new DOMDocument();
if (!$doc->loadXML($manifest->get_content())) {
    elecoa_print_error('incorrectmanifest');
}
$activity_root_node = selectSingleNode($doc->documentElement, 'item');
if (is_null($activity_root_node)) {
    elecoa_print_error('incorrectmanifest');
}
$sgo = !($activity_root_node->getAttribute('oGS') === 'false'); // adlseq:objectivesGlobalToSystem

if (!($log->makeLogReady($uid, $cid, $attempt_number, $sgo))) {
    elecoa_print_error('couldnotmakelogready');
}

// 学習目標のインスタンス化
$objectives = array();
$objective_root_node = selectSingleNode($doc->documentElement, 'objectives');
if (!is_null($objective_root_node)) {
    foreach (selectNodes($objective_root_node, 'objective') as $objective_node) {
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
    foreach (selectNodes($node, 'item') as $child_node) {
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

// セッションにデータを保持
elecoa_session_set_data($elecoa->id, $activities, NULL, $objectives, $elecoa_context);
elecoa_session_set_user($uid, $USER->lastname . ' ' . $USER->firstname);

echo '<!DOCTYPE html>' . "\n";
echo '<html lang="en">' . "\n";
echo ' <head>' . "\n";
echo '  <meta charset="utf-8">' . "\n";
echo '  <title></title>' . "\n";
echo '  <script>' . "\n";
echo '   document.write(\'<base href="' . preg_replace('/choicestart.php.*$/', 'choicestart.php', $_SERVER['REQUEST_URI']) . '">\');' . "\n";
echo '   document.write(\'<script>var elecoa_id = ', $cmid, ', content_id = ', $elecoa->id, ', item_identifier = "', $itemid, '", ownwindow = true;<\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/core.js"><\/script>\');' . "\n";
echo '   document.write(\'<script src="./js/choice.js"><\/script>\');' . "\n";
echo '  </script>' . "\n";
echo ' </head>' . "\n";
echo ' <body>' . "\n";
echo ' </body>' . "\n";
echo '</html>' . "\n";
