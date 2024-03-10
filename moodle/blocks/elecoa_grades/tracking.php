<?php
require_once(dirname(__FILE__) . '/grades_inc.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $CFG, $USER, $DB;

// モバイルかどうか
$ismobile = ($PAGE->devicetypeinuse == 'mobile');

// check for all required variables
$elecoaid = required_param('elecoaid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$attempt = required_param('attempt', PARAM_INT);
$name = required_param('name', PARAM_ALPHANUMEXT);
$parentquery = required_param('parentquery', PARAM_RAW);

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

$parentofparentquery = '';
if (preg_match('/&parentquery=([^&]+)/', $parentquery, $matches)) {
    $parentofparentquery = urldecode($matches[1]);
}

$PAGE->set_url('/blocks/elecoa_grades/tracking.php', array('elecoaid' => $elecoaid, 'userid' => $userid, 'attempt' => $attempt, 'name' => $name, 'parentquery' => $parentquery));
$PAGE->set_title(get_grade_string('runtimeenvdata', false));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_grade_string('gradelist', false), "view.php?$parentofparentquery");
$PAGE->navbar->add(get_grade_string('gradedetails', false), "details.php?$parentquery");
$PAGE->navbar->add(get_grade_string('runtimeenvdata', false));

$CFG->additionalhtmlhead = '<link rel="stylesheet" type="text/css" href="./css/elecoa.css?2013112603" />';

if ($ismobile) {
    $CFG->additionalhtmlhead .= '<link rel="stylesheet" type="text/css" href="./css/elecoa_mobile.css?2013112603" />';
}

// ページヘッダの出力

echo $OUTPUT->header();

if (!$ismobile) {
    echo '<div class="elecoa-grade-csv-link">',
         '<a href="trackingcsv.php?', $_SERVER['QUERY_STRING'], '">',  get_grade_string('downloadascsv'), '</a>',
         '</div>';
}

echo '<h2>', get_grade_string('runtimeenvdata'), '</h2>';


// トラッキング情報一覧の出力

// Load logs
$logs = $DB->get_records_sql('select * from {' . ELECOA_LOGS_TABLE . "} where elecoaid = ? and userid = ? and attempt = ? and name = ? and logkey in ('RTM', 'runtimeXML') order by counter is not null, counter desc", array($elecoaid, $userid, $attempt, $name));
if (empty($logs)) {
    print_error(get_string('notrackingdata', ELECOA_BLOCK_NAME));
}

show_tracking_start();

$counter = 0;
$log_count = count($logs);
foreach ($logs as $log) {
    if (is_null($log->counter)) {
        if ($log_count == 1) {
            $log->counter = 1;
        } else {
            continue;
        }
    }
    if ($ismobile) {
        echo '<li class="aggregate elecoa-mobile-body-d elecoa-mobile-li-static elecoa-mobile-li">';
        echo '<p class="attempt"><strong>', get_grade_string('attempt', false), $log->counter, '</strong></p>';
        echo '</li>';
    } else {
        $class = '';
        if ($counter > 0) {
            $class = ' continue';
        }
        echo '<div class="row row-tracking header', $class, '">';
        echo '<span class="cell counter">', get_grade_string('attempt', false), $log->counter, '</span>';
        echo '</div>';
        echo '<div class="row row-tracking rowhead">';
        cell(get_grade_string('element', false), 'wider center');
        cell(get_grade_string('value', false), 'wider center');
        echo '</div>';
    }
    // Load dom
    $dom = get_dom_from_log($log);
    if (empty($dom)) {
        print_error(get_string('nodata', ELECOA_BLOCK_NAME));
    }
    output_node($dom->documentElement);
    $counter++;
}

show_tracking_end();

// ページフッタの出力

echo $OUTPUT->footer();


function show_tracking_start() {
    global $ismobile;
    if ($ismobile) {
        echo '<ul data-role="listview" class="elecoa-mobile-list">';
    }
}

function show_tracking_end() {
    global $ismobile;
    if ($ismobile) {
        echo '</ul>';
    }
}

/**
 * DOMNodeを出力する。
 *
 * @param object $node DOMNodeオブジェクト。
 * @param string $current_element 現在の要素名。
 */
function output_node($node, $current_element = '') {
    global $ismobile;
    if (empty($node)) {
        return;
    }

    // テキストノードなら出力して終わり
    if ($node->nodeType === XML_TEXT_NODE) {
        if ($ismobile) {
            echo '<li class="tracking elecoa-mobile-li-static elecoa-mobile-body-d elecoa-mobile-li">';
            echo '<p class="element-name">', htmlspecialchars($current_element), '</p>';
            echo '<p class="element-value">', htmlspecialchars($node->textContent), '</p>';
            echo '</li>';
        } else {
            echo '<div class="row row-tracking">';
            cell(htmlspecialchars($current_element), 'wider');
            cell(htmlspecialchars($node->textContent), 'wider center');
            echo '</div>';
        }
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
            output_node($child, $current_element);
        }
    }
}
