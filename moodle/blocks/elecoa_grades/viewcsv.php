<?php
require_once(dirname(__FILE__) . '/grades_inc.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/course/lib.php');

global $CFG, $USER, $DB;

// モバイルかどうか
$ismobile = false;

// パラメータの取得
$courseid = required_param('courseid', PARAM_INT);

$conditions = array(
    'view'            => optional_param('view', 0, PARAM_INT),
    'userid'          => get_numeric_array_parameter('userid', array('0')),
    'itemid'          => get_numeric_array_parameter('itemid', array('0')),
    'completion'      => optional_param('completion', 0, PARAM_INT),
    'success'         => optional_param('success', 0, PARAM_INT),
    'scorefrom'       => (optional_param('scorefrom', '', PARAM_RAW) === '') ? -1.0 : optional_param('scorefrom', -1.0, PARAM_FLOAT),
    'scoreto'         => (optional_param('scoreto', '', PARAM_RAW) === '') ? -1.0 : optional_param('scoreto', -1.0, PARAM_FLOAT),
    'totalperiodfrom' => (optional_param('totalperiodfrom', '', PARAM_RAW) === '') ? -1 : optional_param('totalperiodfrom', -1, PARAM_INT),
    'totalperiodto'   => (optional_param('totalperiodto', '', PARAM_RAW) === '') ? -1 : optional_param('totalperiodto', -1, PARAM_INT),
);

// コースに対するログイン要否をチェック
$course = $DB->get_record('course', array('id' => $courseid));
require_login($course);

// コンテキストに対する権限をチェック
//$context = get_context_instance(CONTEXT_COURSE, $courseid);
$context = context_course::instance($courseid);

$isteacher = has_capability('block/elecoa_grades:teachersview', $context);
$isstudent = has_capability('block/elecoa_grades:studentsview', $context);
if (!$isteacher && !$isstudent) {
    print_error(get_string('notallowed', ELECOA_BLOCK_NAME));
}

// 権限によるパラメータの調整
if (!$isteacher) {
    $conditions['userid'] = array($USER->id);
}

$mod_info = get_fast_modinfo($courseid);
$section_info_all = $mod_info->get_section_info_all();
$sections = array();
foreach ($section_info_all as $section_info) {
    $sections[$section_info->id] = $section_info;
}

// 絞り込みのためのユーザー, 教材一覧を取得
$users = get_grade_users($courseid, true);
$items = get_grade_items($courseid, ($isteacher ? 0 : $USER->id), true);

$filename = 'list' . date('YmdHis') . '.csv';

header('Content-Type: application/x-csv');
header("Content-Disposition: attachment; filename=$filename");

$fp = fopen('php://output', 'w');

// BOM
fwrite($fp, pack('C*',0xEF,0xBB,0xBF));

// 成績一覧表示

show_view_start();

show_view_row(get_grade_string('id'), get_grade_string('user'), get_grade_string('section'),
              get_grade_string('material'),
              get_grade_string('completionstatus'),
              get_grade_string('successstatus'),
              get_grade_string('score'),
              get_grade_string('lessontime'),
              get_grade_string('lessonperiod'),
              get_grade_string('totalperiod'),
              get_grade_string('details'),
              'header');

$records = get_grade_records($courseid, $conditions);
$userid = NULL;
$user_records = array();
foreach ($records as $record) {
    if ($userid !== $record->userid) {
        if (count($user_records) > 0) {
            output_user_aggregation($user_records, $conditions);
            $user_records = array();
        }
    }
    $userid = $record->userid;
    $user_records[] = $record;
}
if (count($user_records) > 0) {
    output_user_aggregation($user_records, $conditions);
}

show_view_end();

fclose($fp);


/////////////////////////////
// フィルタ用関数
/////////////////////////////

// $record は ユーザーと教材毎の集約結果が格納されている.
// $record->id
// $record->user
// $record->completion            : 0 - 完了, 1 - 未了, 2 - 未試行, 3 - 不明
// $record->success               : 0 - 合格, 2 - 不合格, 3 - 不明
// $record->score
// $record->lessontime
// $record->lessonperiod
// $record->totalperiod
// $conditions['view']            : 0 - 通常, 1 - 集約
// $conditions['userid']          : ユーザーID はここではフィルタリングしない
// $conditions['itemid']          : 教材ID はここではフィルタリングしない
// $conditions['completion']      : 0 - すべて, 1 - 完了のみ, 2 - 完了以外
// $conditions['success']         : 0 - すべて, 1 - 合格のみ, 2 - 合格以外
// $conditions['scorefrom']       : 未入力の場合 -1, それ以外 float
// $conditions['scoreto']         : 未入力の場合 -1, それ以外 float
// $conditions['totalperiodfrom'] : 未入力の場合 -1, それ以外 int(分)
// $conditions['totalperiodto']   : 未入力の場合 -1, それ以外 int(分)
function conditions_met($record, $conditions) {
    // completion
    if ($conditions['completion'] == 1 and $record->completion > 0) return false;
    if ($conditions['completion'] == 2 and $record->completion == 0) return false;
    // success
    if ($conditions['success'] == 1 and $record->success > 0) return false;
    if ($conditions['success'] == 2 and $record->success == 0) return false;
    // score
    if($conditions['scorefrom'] > -1 and $record->score < $conditions['scorefrom']) return false;
    if($conditions['scoreto'] > -1 and $record->score > $conditions['scoreto']) return false;
    // totalperiod
    if($conditions['totalperiodfrom'] > -1 and $record->totalperiod < $conditions['totalperiodfrom'] * 60) return false;
    if($conditions['totalperiodto'] > -1 and $record->totalperiod > $conditions['totalperiodto'] * 60) return false;
    return true;
}

/////////////////////////////
// 表示用関数
/////////////////////////////

function show_search_criteria($courseid, $users, $items, $conditions) {
    global $ismobile, $isteacher;
    echo '<div class="elecoa-grade-conditions" data-role="collapsible">';
    if ($ismobile) {
        echo '<h3>', get_grade_string('conditions'), '</h3>';
    }
    echo '<form method="GET">',
         '  <input type="hidden" name="courseid" value="', $courseid, '">',
         // 表示
         '  <label for="elecoa-grade-input-view">', get_grade_string('view'), '</label>',
         '  <select class="update-on-change" name="view" id="elecoa-grade-input-view">';
         show_options(array(0 => get_grade_string('normal'), 1 => get_grade_string('onlyaggregates')), $conditions, 'view');
    echo '  </select>';
    if ($isteacher) {
         // ユーザー
    echo '  <label for="elecoa-grade-input-user">', get_grade_string('user'), '</label>';
         show_selector_users($users, $conditions, 'userid');
    }
         // 教材
    echo '  <label for="elecoa-grade-input-item">', get_grade_string('material'), '</label>';
         show_selector_items($items, $conditions, 'itemid');
         // 完了状態
    echo '  <label for="elecoa-grade-input-completion">', get_grade_string('completionstatus'), '</label>',
         '  <select class="update-on-change" name="completion" id="elecoa-grade-input-completion">';
         show_options(array(0 => get_grade_string('all'), 1 => get_grade_string('onlycompleted'), 2 => get_grade_string('exceptcompleted')), $conditions, 'completion');
    echo '  </select>',
         // 合否
         '  <label for="elecoa-grade-input-success">', get_grade_string('successstatus'), '</label>',
         '  <select class="update-on-change" name="success" id="elecoa-grade-input-success">';
         show_options(array(0 => get_grade_string('all'), 1 => get_grade_string('onlysatisfied'), 2 => get_grade_string('exceptsatisfied')), $conditions, 'success');
    echo '  </select>',
         // 得点
         '  <label for="elecoa-grade-input-scorefrom">', get_grade_string('score'), '</label>',
         '  <input class="update-on-change" type="text" name="scorefrom" id="elecoa-grade-input-scorefrom" size="3" value="', show_input_value($conditions, 'scorefrom', PARAM_FLOAT), '">',
         '  <label class="inputlabel" for="elecoa-grade-input-scoreto" style="display:inline;">', get_grade_string('to'), '</label>',
         '  <input class="update-on-change" type="text" name="scoreto" id="elecoa-grade-input-scoreto" size="3" value="', show_input_value($conditions, 'scoreto', PARAM_FLOAT), '">',
         // 累積学習時間
         '  <label for="elecoa-grade-input-totalperiodfrom">', get_grade_string('totalperiod'), get_grade_string('minutes'), '</label>',
         '  <input class="update-on-change" type="text" name="totalperiodfrom" id="elecoa-grade-input-totalperiodfrom" size="3" value="', show_input_value($conditions, 'totalperiodfrom', PARAM_INT), '">',
         '  <label class="inputlabel" for="elecoa-grade-input-totalperiodto" style="display:inline;">', get_grade_string('to'), '</label>',
         '  <input class="update-on-change" type="text" name="totalperiodto" id="elecoa-grade-input-totalperiodto" size="3" value="', show_input_value($conditions, 'totalperiodto', PARAM_INT), '">',
         '  <input class="submit-button" type="submit" value="', get_grade_string('update'), '">',
         '</form>',
         '</div>';
}

function show_selector_users($users, $conditions, $param) {
    global $ismobile;
    if (!$ismobile) {
        echo '  <select class="update-on-change" name="userid" id="elecoa-grade-input-user" multiple="multiple">';
        foreach ($users as $user) {
            echo '<option value="', htmlspecialchars($user->id), '"', in_array($user->id, $conditions[$param]) ? ' selected="selected"' : '', '>', htmlspecialchars($user->username), '</option>';
        }
        echo '  </select>';
    } else {
        foreach ($users as $user) {
            echo '<input class="update-on-change" type="checkbox"  name="', $param, '" id="elecoa-grade-input-user', htmlspecialchars($user->id), '" value="', htmlspecialchars($user->id), '"', in_array($user->id, $conditions[$param]) ? ' checked="checked"' : '', '><label for="elecoa-grade-input-user', htmlspecialchars($user->id), '">', htmlspecialchars($user->username), '</label>';
        }
    }
}

function show_selector_items($items, $conditions, $param) {
    global $ismobile;
    if (!$ismobile) {
        echo '  <select class="update-on-change" name="itemid" id="elecoa-grade-input-item" multiple="multiple">';
        foreach ($items as $item) {
            echo '<option value="', htmlspecialchars($item->elecoaid), '"', in_array($item->elecoaid, $conditions[$param]) ? ' selected="selected"' : '', '>', htmlspecialchars($item->title), '</option>';
        }
        echo '  </select>';
    } else {
        foreach ($items as $item) {
            echo '<input class="update-on-change" type="checkbox"  name="', $param, '" id="elecoa-grade-input-item', htmlspecialchars($item->id), '" value="', htmlspecialchars($item->id), '"', in_array($item->id, $conditions['itemid']) ? ' checked="checked"' : '', '><label for="elecoa-grade-input-item', htmlspecialchars($item->id), '">', htmlspecialchars($item->title), '</label>';
        }
    }
}

function show_view_start() {
    global $ismobile;
    if ($ismobile) {
        echo '<ul data-role="listview" class="elecoa-mobile-list">';
    }
}

function show_view_end() {
    global $ismobile;
    if ($ismobile) {
        echo '</ul>';
    }
}

function show_view_row($id, $user, $section, $name, $completion, $success, $score, $lessontime, $lessonperiod, $totalperiod, $details, $type) {
    global $ismobile;
    $class = 'row';
    switch ($type) {
        case 'header':
            $class = 'header';
            break;
        case 'row':
            $class = 'row';
            break;
        case 'aggregation':
            $class = 'row aggregation';
            break;
        default:
            break;
    }
    if ($ismobile) {
        if ($type == 'header') {
            echo '<li data-role="list-divider">', get_grade_string('gradelist'), '</li>';
        }
        if ($type == 'row') {
            echo '<li>';
            echo '<a href="">';
            echo '<p class="firstname lastname">', htmlspecialchars($user), '</p>';
            echo '<p class="name"><strong>', htmlspecialchars($name), '</strong></p>';
            echo '<p class="ui-li-aside completion success">', $completion, ' / ', $success, ' / ', get_grade_string('score'), $score, '</p>';
            echo '<p class="totalperiod"><span class="caption">', get_grade_string('totalperiod'), '</span>', $totalperiod, '</p>';
            echo '</a>';
            echo '</li>';
        }
        if ($type == 'aggregation') {
            echo '<li class="aggregate">';
            echo '<p class="userlongname">', htmlspecialchars($user), '</p>';
            echo '<p class="name"><strong>', htmlspecialchars($name), '</strong></p>';
            echo '<p class="ui-li-aside completion success">', $completion, ' / ', $success, ' / ', get_grade_string('score'), $score, '</p>';
            echo '<p class="totalperiod"><span class="caption">' . get_grade_string('totalperiod'), '</span>', $totalperiod, '</p>';
            echo '</li>';
        }
    } else {
        global $fp;

        $row = array();
        $row[] = $id;
        $row[] = $user;
        $row[] = $section;
        $row[] = $name;
        $row[] = $completion;
        $row[] = $success;
        $row[] = $score;
        $row[] = $lessontime;
        $row[] = $lessonperiod;
        $row[] = $totalperiod;

        fputcsv($fp, $row);
    }
}

function output_user_item_aggregation($user_item_records, $conditions) {
    global $course, $sections;
    $id = NULL;
    $user = NULL;
    $name = NULL;
    $section = NULL;
    $completion = 3; // Unknown
    $success = 2; // Unknown
    $score = NULL;
    $lessontime = NULL;
    $lessonperiod = NULL;
    $totalperiod = 0;
    $id = $user_item_records[0]->username;
    $userid = $user_item_records[0]->userid;
    $elecoaid = $user_item_records[0]->elecoaid;
    $user = get_fullname($user_item_records[0]->lastname, $user_item_records[0]->firstname);
    $name = $user_item_records[0]->name;
    $section = $sections[$user_item_records[0]->course_section];
    $section_name = get_section_name($course, $section);
    // 最後の学習回の最終学習終了日時
    $lessontime = $user_item_records[0]->lessontime;
    // 最後の学習回の最終学習時間
    $lessonperiod = $user_item_records[0]->lessonperiod;
    // 集計
    foreach ($user_item_records as $user_item_record) {
        if (!is_null($user_item_record->completion) && $completion > $user_item_record->completion) {
            $completion = $user_item_record->completion;
        }
        if (!is_null($user_item_record->success) && $success > $user_item_record->success) {
            $success = $user_item_record->success;
        }
        if (!is_null($user_item_record->score) && $score < $user_item_record->score) {
            $score = $user_item_record->score;
        }
        $totalperiod += $user_item_record->totalperiod;
    }

    $result = (object)array('id' => $id, 'user' => $user,
                            'completion' => $completion, 'success' => $success, 'score' => $score,
                            'lessontime' => $lessontime, 'lessonperiod' => $lessonperiod, 'totalperiod' => $totalperiod);

    $is_conditions_met = conditions_met($result, $conditions);
    $query_string = $_SERVER['QUERY_STRING'];
    $details = '<a href="details.php?courseid=' . $course->id . '&amp;userid=' . $userid. '&amp;elecoaid=' . $elecoaid . '&amp;parentquery=' . urlencode($query_string) . '">' . get_grade_string('details') . '</a>';
    if ($conditions['view'] == 0 and $is_conditions_met) {
        show_view_row($id, $user, $section_name, $name,
                      get_completion_string($completion),
                      get_success_string($success),
                      get_score_string($score, true),
                      get_datetime_string($lessontime),
                      get_time_string($lessonperiod),
                      get_time_string($totalperiod),
                      $details,
                      'row');
    }
    return $is_conditions_met ? $result : NULL;
}

function output_user_aggregation($user_records, $conditions) {
    $itemid = NULL;
    $user_item_records = array();
    $results = array();
    foreach ($user_records as $user_record) {
        if ($itemid !== $user_record->itemid) {
            if (count($user_item_records) > 0) {
                $result = output_user_item_aggregation($user_item_records, $conditions);
                if (!is_null($result)) {
                    $results[] = $result;
                }
                $user_item_records = array();
            }
        }
        $itemid = $user_record->itemid;
        $user_item_records[] = $user_record;
    }
    if (count($user_item_records) > 0) {
        $result = output_user_item_aggregation($user_item_records, $conditions);
        if (!is_null($result)) {
            $results[] = $result;
        }
    }
    $id = NULL;
    $user = NULL;
    $completion = 1; // incomplete
    $completion_sum = 0;
    $success = 1; // notsatisfied
    $success_sum = 0;
    $score = NULL;
    $score_sum = 0;
    $lessontime = NULL;
    $lessonperiod = NULL;
    $totalperiod = 0;
    $count = count($results);
    if ($count == 0) return;
    foreach ($results as $result) {
        $id = $result->id;
        $user = $result->user;
        $completion_sum += $result->completion;
        $success_sum += $result->success;
        $score_sum += $result->score;
        // ユーザー毎の集約の最終学習終了日時は最後に学習を終了した教材の最終学習終了日時
        // ユーザー毎の集約の最終学習時間は最後に学習を終了した教材の最終学習時間
        if ($lessontime < $result->lessontime) {
            $lessontime = $result->lessontime;
            $lessonperiod = $result->lessonperiod;
        }
        $totalperiod += $result->totalperiod;
    }
    if ($count > 0) {
        $score = $score_sum / $count;
    } else {
        $score = 0;
    }
    // すべての教材を完了していれば完了
    if ($completion_sum === 0) {
        $completion = 0;
    }
    // すべての教材が不明であれば不明
    if ($completion_sum === (3 * $count)) {
        $completion = 3;
    }
    // すべての教材を合格していれば合格
    if ($success_sum === 0) {
        $success = 0;
    }
    // すべての教材が不明であれば不明
    if ($success_sum === (2 * $count)) {
        $success = 2;
    }
    show_view_row($id, $user, get_grade_string('aggregate'), get_grade_string('aggregate'),
                  get_completion_string($completion),
                  get_success_string($success),
                  get_score_string($score, true),
                  get_datetime_string($lessontime),
                  get_time_string($lessonperiod),
                  get_time_string($totalperiod),
                  '&nbsp;',
                  'aggregation');
}
