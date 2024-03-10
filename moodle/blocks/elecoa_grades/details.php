<?php
require_once(dirname(__FILE__) . '/grades_inc.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/course/lib.php');

global $CFG, $USER, $DB;

// モバイルかどうか
$ismobile = ($PAGE->devicetypeinuse == 'mobile');

// check for all required variables
$courseid    = required_param('courseid', PARAM_INT);
$userid      = required_param('userid', PARAM_INT);
$elecoaid    = required_param('elecoaid', PARAM_INT);
$parentquery = required_param('parentquery', PARAM_RAW);
// itemid が指定されている場合、その要素を親に持つ要素のみのリストを作成し
// 同時に itemid を持つ要素のトラッキング情報を表示する(モバイルのみ)
$itemid      = optional_param('itemid', 0, PARAM_INT);
$attempt     = optional_param('attempt', 0, PARAM_INT);
$showmode    = optional_param('showmode', 1, PARAM_INT);
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

// Load user & elecoa
$user = $DB->get_record('user', array('id' => $userid));
$elecoa = $DB->get_record(ELECOA_TABLE, array('id' => $elecoaid));
if ($user == null || $elecoa == null) {
    print_error(get_string('nodata', ELECOA_BLOCK_NAME));
}

$PAGE->set_url('/blocks/elecoa_grades/details.php', array('courseid' => $courseid, 'userid' => $userid, 'elecoaid' => $elecoaid, 'parentquery' => $parentquery));
$PAGE->set_title(get_grade_string('gradedetails', false));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_grade_string('gradelist', false), "view.php?$parentquery");
$PAGE->navbar->add(get_grade_string('gradedetails', false));

$CFG->additionalhtmlhead = '<link rel="stylesheet" type="text/css" href="./css/elecoa.css?2013112603" />';

if ($ismobile) {
    $CFG->additionalhtmlhead .= '<link rel="stylesheet" type="text/css" href="./css/elecoa_mobile.css?2013112603" />';
}

echo $OUTPUT->header();

// CSV リンクの出力
if (!$ismobile) {
    echo '<div class="elecoa-grade-csv-link">',
         '<a href="detailscsv.php?', $_SERVER['QUERY_STRING'], '">', get_grade_string('downloadascsv'), '</a>',
         '</div>';
}

echo '<h2>', htmlspecialchars(get_fullname($user->lastname, $user->firstname) . ' (' . $user->username . ')'), ' - ', htmlspecialchars($elecoa->name), '</h2>';

if ($showmode === 1) {
    echo '<div class="elecoa-grade-conditions">',
         '<form method="GET">',
         '  <input type="hidden" name="courseid" value="', $courseid, '" />',
         '  <input type="hidden" name="userid" value="', $userid, '" />',
         '  <input type="hidden" name="elecoaid" value="', $elecoaid, '" />';
         if ($itemid > 0) {
    echo '  <input type="hidden" name="itemid" value="', $itemid, '" />';
         }
         if ($attempt > 0) {
    echo '  <input type="hidden" name="attempt" value="', $attempt, '" />';
         }
    echo '  <input type="hidden" name="parentquery" value="', htmlspecialchars($parentquery), '" />';
         // 表示成績
    echo '  <label for="elecoa-grade-input-view">', get_grade_string('countingmode'), '</label>';
    echo '  <select class="update-on-change" name="view" id="elecoa-grade-input-view">';
         show_options(array(0 => get_grade_string('modebest'), 1 => get_grade_string('modelatest')), $conditions, 'view');
    echo '  </select>';
    echo '</form>',
         '</div>';
}

$iscoutingmode_latest = false;
if ($conditions['view'] == 1) {
    $iscoutingmode_latest = true;
}

show_details_start();

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

show_details_end();

if ($ismobile and $itemid !== 0) {
    // モバイルの場合 トラッキング情報の表示
    // Load logs
    $logs = $DB->get_records_sql('select logs.id, logs.counter, logs.logkey, logs.logvalue from {' . ELECOA_LOGS_TABLE . "} as logs inner join {" . ELECOA_ITEMS_TABLE . "} as items on logs.elecoaid = items.elecoaid and logs.name = items.identifier where logs.elecoaid = ? and logs.userid = ? and logs.attempt = ? and items.id = ? and logs.logkey in ('RTM', 'runtimeXML') order by logs.counter is not null, logs.counter desc", array($elecoaid, $userid, $attempt, $itemid));
    if (empty($logs)) {
        print_error(get_string('notrackingdata', ELECOA_BLOCK_NAME));
    }
    $counter = 0;
    $log_count = count($logs);
    echo '<ul data-role="listview" class="elecoa-mobile-list">';
    foreach ($logs as $log) {
        if (is_null($log->counter)) {
            if ($log_count == 1) {
                $log->counter = 1;
            } else {
                continue;
            }
        }
        echo '<li class="aggregate elecoa-mobile-body-d elecoa-mobile-li-static elecoa-mobile-li">';
        echo '<p class="attempt"><strong>', get_grade_string('attempt', false), $log->counter, '</strong></p>';
        echo '</li>';
        // Load dom
        $dom = get_dom_from_log($log);
        if (empty($dom)) {
            print_error(get_string('nodata', ELECOA_BLOCK_NAME));
        }
        output_node($dom->documentElement);
        $counter++;
    }
    echo '</ul>';
}

echo '<script type="text/javascript">YUI().use("event", "node", function(Y) { Y.on("domready", function() { Y.on("change", function() { Y.one(this).ancestor("form").submit(); }, ".elecoa-grade-conditions .update-on-change");}); });</script>';

echo $OUTPUT->footer();


//////////////////////////////
// 表示用関数
//////////////////////////////

function show_details_start() {
    global $ismobile;
    if ($ismobile) {
        echo '<ul data-role="listview" class="elecoa-mobile-list">';
    }
}

function show_details_end() {
    global $ismobile;
    if ($ismobile) {
        echo '</ul>';
    }
}

function show_details_row($title, $completion, $success, $score, $lessontime, $lessonperiod, $totalperiod, $tracking, $type) {
    global $ismobile;
    if ($ismobile) {
        if ($tracking === '&nbsp;') {
            return;
        }
        if ($type === 'header') {
            echo '<li data-role="list-divider" class="elecoa-mobile-li elecoa-mobile-list-divider elecoa-mobile-bar-b">', get_grade_string('gradedetails'), '</li>';
        }
        if ($type === 'row') {
            // mymobileテーマ対応: 矢印アイコンを表示しないためには data-icon="false" を指定する
            $starts_with = function($s, $target) {
                return $target === '' || strpos($s, $target) === 0;
            };
            $nolink = $starts_with($tracking, '<a href=""');
            echo '<li class="elecoa-mobile-btn elecoa-mobile-btn-up-d elecoa-mobile-li"', ($nolink ? ' data-icon="false"' : '') , '>';
            echo $tracking;
            echo '<p class="elecoa-mobile-li-aside completion success">', $completion, ' / ', $success, ' / ', ($score ? (get_grade_string('score') . $score) : get_grade_string('noscore')), '</p>';
            echo '<p class="lessontime">', $lessontime, '</p>';
            echo '<p class="name"><strong>', htmlspecialchars($title), '</strong></p>';
            echo '<p class="totalperiod"><span class="caption">' . get_grade_string('lessonperiod'), '</span>', $lessonperiod, '<span class="caption">' . get_grade_string('totalperiod'), '</span>', $totalperiod, '</p>';
            echo '</a>';
            echo '</li>';
        }
        if ($type === 'root') {
            echo '<li class="aggregate elecoa-mobile-body-d elecoa-mobile-li">';
            echo $tracking;
            echo '<p class="elecoa-mobile-li-aside completion success">', $completion, ' / ', $success, ' / ', ($score ? (get_grade_string('score') . $score) : get_grade_string('noscore')), '</p>';
            echo '<p class="lessontime">', $lessontime, '</p>';
            echo '<p class="name"><strong>', htmlspecialchars($title), '</strong></p>';
            echo '<p class="totalperiod"><span class="caption">' . get_grade_string('lessonperiod'), '</span>', $lessonperiod, '<span class="caption">' . get_grade_string('totalperiod'), '</span>', $totalperiod, '</p>';
            echo '</a>';
            echo '</li>';
        }
    } else {
        $class = 'row row-details';
        $title_class = 'wide';
        switch ($type) { // header, root, row
            case 'header':
                $class = 'header row-details';
                $title_class = 'wide center';
                break;
            case 'root';
                $class = 'row row-details root';
                break;
        }
        echo '<div class="', $class, '">';
        cell($title, $title_class);
        cell($completion, 'narrow center');
        cell($success, 'narrow center');
        cell($score, 'narrow center');
        cell($lessontime, 'wide center');
        cell($lessonperiod, 'center');
        cell($totalperiod, 'center');
        cell($tracking, 'wide center');
        echo '</div>';
    }
}

function output_attempt_item($item, $depth, $parentid) {
    global $iscoutingmode_latest, $ismobile, $courseid, $itemid, $attempt;
    $should_this_row_display = true;
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
    if ($ismobile) {
        $should_this_row_display = ($itemid === $parentid and intval($item->attempt) === $attempt);
        $span = htmlspecialchars($item->title);
        // リーフの場合は次の画面はトラッキング情報のみの表示になるため「最良」「最新」の選択は出力しない
        $showmode = true;
        if ($item->cotype === 'SCORMSco' or $item->cotype === 'SCORMAsset') {
            $showmode = false;
        }
    } else {
        switch ($item->cotype) {
            case 'SCORMRoot':
                $span_class = 'coroot';
                break;
            case 'SCORMBlock':
                $span_class = 'coblock';
                break;
            case 'SCORMSco':
            case 'SCORMAsset':
                $span_class = 'coleaf';
                break;
        }
        $padding_left = $depth * 20;
        $text_indent = $depth * 10;
        $span = '<span class="' . $span_class . "\" style=\"padding-left:${padding_left}px;text-indent:-${text_indent}px;display:inline-block;\">" . htmlspecialchars($item->title) . "</span>";
    }
    if ($details_exists) {
        $query_string = $_SERVER['QUERY_STRING'];
        $detail_elecoaid = $item->details[0]->elecoaid;
        if ($ismobile) {
            $link = '<a href="details.php?courseid=' . $courseid . '&amp;elecoaid=' . $detail_elecoaid . '&amp;userid=' . $item->details[0]->userid . '&amp;itemid=' . urlencode($item->details[0]->itemid) . '&amp;attempt=' . urlencode($item->attempt) . ($iscoutingmode_latest ? '&amp;view=1' : '&amp;view=0') . ($showmode ? '' : '&amp;showmode=0') . '&amp;parentquery=' . urlencode($query_string) . '">';
        } else {
            $link = '<a href="tracking.php?elecoaid=' . $detail_elecoaid . '&amp;userid=' . $item->details[0]->userid . '&amp;attempt=' . $item->details[0]->attempt . '&amp;name=' . urlencode($item->details[0]->identifier) . '&amp;parentquery=' . urlencode($query_string) . '">' . get_grade_string('trackinginfo') . '</a>';
        }
        $should_this_row_display &&
        show_details_row($span,
                         // 完了状態
                         get_completion_string($completion),
                         // 合否
                         get_success_string($success),
                         // 得点
                         get_score_string($score),
                         // 最終学習終了日時
                         get_datetime_string($item->details[0]->lessontime),
                         // 最終学習時間
                         get_time_string($item->details[0]->lessonperiod),
                         // 累積学習時間
                         get_time_string($item->totalperiod),
                         // トラッキング情報または下位階層(モバイルの場合)へのリンク
                         $link,
                         'row');
    } else {
        $query_string = $_SERVER['QUERY_STRING'];
        if ($ismobile) {
            $link = '<a href="" onclick="return false;">';
        } else {
            $link = '&nbsp;';
        }
        $should_this_row_display &&
        show_details_row($span,
                         // 完了状態
                         get_completion_string($item->completion),
                         // 合否
                         get_success_string($item->success),
                         // 得点
                         get_score_string($item->score),
                         // 最終学習終了日時
                         get_datetime_string($item->lessontime),
                         // 最終学習時間
                         get_time_string($item->lessonperiod),
                         // 累積学習時間
                         get_time_string($item->totalperiod),
                         // リンク
                         $link,
                         'row');
    }
    foreach ($item->children as $child) {
        output_attempt_item($child, $depth + 1, intval($item->itemid));
    }
}

// 学習回ごとに出力
function output_attempt_items($record) {
    global $ismobile, $courseid, $itemid, $iscoutingmode_latest;
    $should_this_row_display = true;
    $query_string = $_SERVER['QUERY_STRING'];
    $record_elecoaid = $record->elecoaid;
    if ($ismobile) {
        $should_this_row_display = ($itemid === 0);
        $link = '<a href="details.php?courseid=' . $courseid . '&amp;elecoaid=' . $record_elecoaid . '&amp;userid=' . $record->userid . '&amp;itemid=' . urlencode($record->itemid) . '&amp;attempt=' . urlencode($record->attempt) . ($iscoutingmode_latest ? '&amp;view=1' : '&amp;view=0') . '&amp;parentquery=' . urlencode($query_string) . '">';
    } else {
        $link = '<a href="tracking.php?elecoaid=' . $record_elecoaid . '&amp;userid=' . $record->userid . '&amp;attempt=' . $record->attempt . '&amp;name=' . urlencode($record->identifier) . '&amp;parentquery=' . urlencode($query_string) . '">' . get_grade_string('trackinginfo') . '</a>';
    }
    $should_this_row_display &&
    show_details_row(sprintf(get_grade_string('ordinalformat', false), $record->attempt),
                     // 完了状態
                     get_completion_string($record->completion),
                     // 合否
                     get_success_string($record->success),
                     // 得点
                     get_score_string($record->score),
                     // 最終学習終了日時
                     get_datetime_string($record->lessontime),
                     // 最終学習時間
                     get_time_string($record->totalperiod),
                     // 累積学習時間
                     get_time_string($record->totalperiod),
                     // トラッキング情報または下位階層(モバイルの場合)へのリンク
                     $link,
                     'root');
    foreach ($record->children as $item) {
        output_attempt_item($item, 1 /* depth */, intval($record->itemid));
    }
}

function output_node($node, $current_element = '') {
    if (empty($node)) {
        return;
    }

    // テキストノードなら出力して終わり
    if ($node->nodeType === XML_TEXT_NODE) {
        echo '<li class="tracking elecoa-mobile-li-static elecoa-mobile-body-d elecoa-mobile-li">';
        echo '<p class="element-name">', htmlspecialchars($current_element), '</p>';
        echo '<p class="element-value">', htmlspecialchars($node->textContent), '</p>';
        echo '</li>';
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
