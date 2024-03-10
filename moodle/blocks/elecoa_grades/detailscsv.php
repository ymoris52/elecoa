<?php
require_once(dirname(__FILE__) . '/grades_inc.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/course/lib.php');

global $CFG, $USER, $DB;

// check for all required variables
$courseid    = required_param('courseid', PARAM_INT);
$userid      = required_param('userid', PARAM_INT);
$elecoaid    = required_param('elecoaid', PARAM_INT);
$conditions = array(
    'view' => optional_param('view', 0, PARAM_INT),
);

// Load course
$course = $DB->get_record('course', array('id' => $courseid));

// ensure the user has access to this course
require_login($course);

// get context
//$context = get_context_instance(CONTEXT_COURSE, $courseid);
$context = context_course::instance($courseid);

// check capability
$isteacher = has_capability('block/elecoa_grades:teachersview', $context);
$isstudent = has_capability('block/elecoa_grades:studentsview', $context);
if (!$isteacher && !$isstudent) {
    print_error(get_string('notallowed', ELECOA_BLOCK_NAME));
}
if (!$isteacher && ($userid != $USER->id)) {
    print_error(get_string('notallowed', ELECOA_BLOCK_NAME));
}

$filename = 'details' . date('YmdHis') . '.csv';

header('Content-Type: application/x-csv');
header("Content-Disposition: attachment; filename=$filename");

$fp = fopen('php://output', 'w');

// BOM
fwrite($fp, pack('C*',0xEF,0xBB,0xBF));


$iscoutingmode_latest = false;
if ($conditions['view'] == 1) {
    $iscoutingmode_latest = true;
}

show_details_row(get_grade_string('title', false),
                 get_grade_string('completionstatus', false),
                 get_grade_string('successstatus', false),
                 get_grade_string('score', false),
                 get_grade_string('lessontime', false),
                 get_grade_string('lessonperiod', false),
                 get_grade_string('totalperiod', false),
                 get_grade_string('trackinginfo', false),
                 'header');

$records = get_user_grade_records($userid, $elecoaid);

foreach ($records as $record) {
    output_attempt_items($record);
}

fclose($fp);


//////////////////////////////
// 表示用関数
//////////////////////////////

function show_details_row($title, $completion, $success, $score, $lessontime, $lessonperiod, $totalperiod, $tracking, $type) {
    global $fp;
    $class = 'row row-details';
    $title_class = 'wide';
    switch ($type) {
        case 'header':
            $class = 'header row-details';
            $title_class = 'wide center';
            break;
        case 'root';
            $class = 'row row-details root';
            break;
    }

    $row = array();
    $row[] = $title;
    $row[] = $completion;
    $row[] = $success;
    $row[] = $score;
    $row[] = $lessontime;
    $row[] = $lessonperiod;
    $row[] = $totalperiod;

    fputcsv($fp, $row);
}

function output_attempt_item($item, $depth) {
    global $iscoutingmode_latest;
    $details_exists = (count($item->details) > 0);
    if ($details_exists) {
        if ($iscoutingmode_latest) {
            //集約[最新]
            $completion = $item->details[0]->completion;
            $success = $item->details[0]->success;
            $score = $item->details[0]->score;
        } else {
            //集約[最良]
            $completion = 3; // Unknown
            $success = 2; // Unknown
            $score = null;
            foreach ($item->details as $d) {
                if ($d->completion < $completion) {
                    $completion = $d->completion;
                }
                if ($d->success < $success) {
                    $success = $d->success;
                }
                if ($d->score > $score) {
                    $score = $d->score;
                }
            }
        }
    }
    //タイトル
    $padding_left = $depth * 10;
    $width = 130 - $depth * 10;
    $span = htmlspecialchars($item->title);
    if ($details_exists) {
        $query_string = $_SERVER['QUERY_STRING'];
        $trackinginfo = '<a href="tracking.php?elecoaid=' . $item->details[0]->elecoaid . '&amp;userid=' . $item->details[0]->userid . '&amp;attempt=' . $item->details[0]->attempt . '&amp;name=' . urlencode($item->details[0]->identifier) . '&amp;parentquery=' . urlencode($query_string) . '">' . get_grade_string('trackinginfo') . '</a>';
        show_details_row($span,
                         // 完了状態
                         get_completion_string($completion),
                         // 合否
                         get_success_string($success),
                         // 得点
                         get_score_string($score, true),
                         // 最終学習終了日時
                         get_datetime_string($item->details[0]->lessontime),
                         // 最終学習時間
                         get_time_string($item->details[0]->lessonperiod),
                         // 累積学習時間
                         get_time_string($item->totalperiod),
                         // トラッキング情報
                         $trackinginfo,
                         'row');
    } else {
        show_details_row($span,
                         // 完了状態
                         '',
                         // 合否
                         '',
                         // 得点
                         '',
                         // 最終学習終了日時
                         '',
                         // 最終学習時間
                         '',
                         // 累積学習時間
                         '',
                         // トラッキング情報
                         '',
                         'row');
    }
    foreach ($item->children as $child) {
        output_attempt_item($child, $depth + 1);
    }
}

// 学習回ごとに出力
function output_attempt_items($record) {
    $query_string = $_SERVER['QUERY_STRING'];
    $trackinginfo = '<a href="tracking.php?elecoaid=' . $record->elecoaid . '&amp;userid=' . $record->userid . '&amp;attempt=' . $record->attempt . '&amp;name=' . urlencode($record->identifier) . '&amp;parentquery=' . urlencode($query_string) . '">' . get_grade_string('trackinginfo') . '</a>';
    show_details_row(sprintf(get_grade_string('ordinalformat', false), $record->attempt),
                     // 完了状態
                     get_completion_string($record->completion),
                     // 合否
                     get_success_string($record->success),
                     // 得点
                     get_score_string($record->score, true),
                     // 最終学習終了日時
                     get_datetime_string($record->lessontime),
                     // 最終学習時間
                     get_time_string($record->totalperiod),
                     // 累積学習時間
                     get_time_string($record->totalperiod),
                     // トラッキング情報
                     $trackinginfo,
                     'root');
    foreach ($record->children as $item) {
        output_attempt_item($item, 1 /* depth */);
    }
}
