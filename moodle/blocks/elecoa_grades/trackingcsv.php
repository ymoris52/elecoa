<?php
require_once(dirname(__FILE__) . '/grades_inc.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $CFG, $USER, $DB;

// check for all required variables
$elecoaid = required_param('elecoaid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$attempt = required_param('attempt', PARAM_INT);
$name = required_param('name', PARAM_ALPHANUMEXT);
$count = optional_param('count', 0, PARAM_INT);

// Load elecoa
$elecoa = $DB->get_record(ELECOA_TABLE, array('id' => $elecoaid));
if (empty($elecoa)) {
    print_error(get_string('noelecoadata', ELECOA_BLOCK_NAME));
}

//// ensure the user has access to this course
require_login($elecoa->course);

// get context
//$context = get_context_instance(CONTEXT_COURSE, $elecoa->course);
$context = context_course::instance($elecoa->course);

// check capability
$isteacher = has_capability('block/elecoa_grades:teachersview', $context);
$isstudent = has_capability('block/elecoa_grades:studentsview', $context);
if (!$isteacher && !$isstudent) {
    print_error(get_string('notallowed', ELECOA_BLOCK_NAME));
}
if (!$isteacher && ($userid != $USER->id)) {
    print_error(get_string('notallowed', ELECOA_BLOCK_NAME));
}

// Load course
$course = $DB->get_record('course', array('id' => $elecoa->course));
if (empty($course)) {
    print_error(get_string('nocoursedata', ELECOA_BLOCK_NAME));
}

// Load logs
$logs = $DB->get_records_sql('select * from {' . ELECOA_LOGS_TABLE . "} where elecoaid = ? and userid = ? and attempt = ? and name = ? and logkey in ('RTM', 'runtimeXML') order by counter is not null, counter desc", array($elecoaid, $userid, $attempt, $name));
if (empty($logs)) {
    print_error(get_string('notrackingdata', ELECOA_BLOCK_NAME));
}

$filename = 'rte' . date('YmdHis') . '.csv';

header('Content-Type: application/x-csv');
header("Content-Disposition: attachment; filename=$filename");

$fp = fopen('php://output', 'w');

// BOM
fwrite($fp, pack('C*',0xEF,0xBB,0xBF));

// ヘッダ
$headerrow = array();
$headerrow[] = get_grade_string('attempt', false);
$headerrow[] = get_grade_string('element', false);
$headerrow[] = get_grade_string('value', false);
fputcsv($fp, $headerrow);

$log_count = count($logs);
foreach ($logs as $log) {
    if (is_null($log->counter)) {
        if ($log_count == 1) {
            $log->counter = 1;
        } else {
            continue;
        }
    }
    // Load dom
    $dom = get_dom_from_log($log);
    if (empty($dom)) {
        print_error(get_string('nodata', ELECOA_BLOCK_NAME));
    }
    output_node($fp, $log->counter, $dom->documentElement);
}

fclose($fp);


/**
 * DOMNodeを出力する。
 *
 * @param object $node DOMNodeオブジェクト。
 * @param string $current_element 現在の要素名。
 */
function output_node(&$fp, $counter, $node, $current_element = '') {
    if (empty($node)) {
        return;
    }

    // テキストノードなら出力して終わり
    if ($node->nodeType === XML_TEXT_NODE) {
        $row = array();
        $row[] = $counter;
        $row[] = $current_element;
        $row[] = $node->textContent;
        
        //mb_convert_variables('sjis-win', 'UTF-8', $row);
        fputcsv($fp, $row);
        return;
    }

    // 要素名の生成
    if (!empty($current_element)) {
        $current_element .= '.';
    }
    if (preg_match('/^i(\d+)$/', $node->nodeName, $matches)) {
        $current_element .= $matches[1];
    }
    else {
        $current_element .= $node->nodeName;
    }

    // 子ノードがあれば子ノードを出力
    if ($node->hasChildNodes()) {
        foreach ($node->childNodes as $child) {
            output_node($fp, $counter, $child, $current_element);
        }
    }
}
