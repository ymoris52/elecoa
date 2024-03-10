<?php
require_once(dirname(__FILE__) . '/init.php');

define('ELECOA_TABLE',        'elecoa');
define('ELECOA_LOGS_TABLE',   'elecoa_logs');
define('ELECOA_ITEMS_TABLE',  'elecoa_items');
define('ELECOA_GRADES_TABLE', 'elecoa_grades');

function get_grade_records($courseid, $conditions, $outputexception = false) {
    global $DB;

    $table_elecoa = '{' . ELECOA_TABLE . '}';
    $table_elecoa_grades = '{' . ELECOA_GRADES_TABLE . '}';
    $table_elecoa_items = '{' . ELECOA_ITEMS_TABLE . '}';
    $table_user = '{user}';
    $table_course = '{course}';
    $table_course_modules = '{course_modules}';
    $table_course_sections = '{course_sections}';

    try {
        $sql =
            "SELECT"
              . " $table_elecoa_grades.*"
              . ", $table_elecoa_items.title AS item_title"
              . ", $table_user.username"
              . ", $table_user.firstname"
              . ", $table_user.lastname"
              . ", $table_elecoa.name"
              . ", $table_course_modules.section AS course_section"
          . " FROM"
              . " $table_elecoa_grades"
              . " INNER JOIN $table_elecoa ON $table_elecoa_grades.elecoaid = $table_elecoa.id"
              . " INNER JOIN $table_course ON $table_elecoa.course = $table_course.id"
              . " INNER JOIN $table_course_modules ON $table_elecoa.coursemodule = $table_course_modules.id"
              . " INNER JOIN $table_course_sections ON $table_course_sections.id = $table_course_modules.section"
              . " INNER JOIN $table_elecoa_items ON $table_elecoa_grades.itemid = $table_elecoa_items.id"
              . " INNER JOIN $table_user ON $table_elecoa_grades.userid = $table_user.id"
          . " WHERE"
              . " $table_elecoa.course = $courseid"
              . " AND $table_elecoa_grades.parentid IS NULL";

        if ($conditions['userid'] && is_array($conditions['userid']) && (count($conditions['userid']) > 0) && !((count($conditions['userid']) == 1) && ($conditions['userid'][0] == 0))) {
            $sql .= " AND $table_elecoa_grades.userid IN (" . implode(', ', $conditions['userid']) . ")";
        }
        if ($conditions['itemid'] && is_array($conditions['itemid']) && (count($conditions['itemid']) > 0) && !((count($conditions['itemid']) == 1) && ($conditions['itemid'][0] == 0))) {
            $sql .= " AND $table_elecoa_grades.elecoaid IN (" . implode(', ', $conditions['itemid']) . ")";
        }

        $sql .=
            " ORDER BY"
              . " $table_elecoa_grades.userid"
              . ", $table_course_sections.section"
              . ", $table_elecoa_grades.itemid"
              . ", $table_elecoa_grades.attempt DESC;";

        return $DB->get_records_sql($sql);
    } catch (Exception $e) {
        if ($outputexception) {
            echo var_export($e, true);
        }

        throw $e;
    }
}

function get_user_grade_records($userid, $elecoaid, $attempt = null, $parentid = null) {
    global $DB;

    $table_elecoa = '{' . ELECOA_TABLE . '}';
    $table_elecoa_grades = '{' . ELECOA_GRADES_TABLE . '}';
    $table_elecoa_items = '{' . ELECOA_ITEMS_TABLE . '}';

    try {
        $sql =
            "SELECT"
              . " $table_elecoa_grades.*"
              . ", $table_elecoa_items.title"
              . ", $table_elecoa_items.cotype"
              . ", $table_elecoa_items.identifier"
          . " FROM"
              . " $table_elecoa_grades"
              . " INNER JOIN $table_elecoa ON $table_elecoa_grades.elecoaid = $table_elecoa.id"
              . " INNER JOIN $table_elecoa_items ON $table_elecoa_grades.itemid = $table_elecoa_items.id"
          . " WHERE"
              . " $table_elecoa_grades.elecoaid = $elecoaid"
              . " AND $table_elecoa_grades.userid = $userid"
              . " AND $table_elecoa_grades.parentid " . (is_null($parentid) ? 'IS NULL' : '= ' . $parentid)
              . " AND $table_elecoa_grades.counter IS NULL";

        if (!is_null($attempt)) {
            $sql .=
                " AND $table_elecoa_grades.attempt = $attempt"
          . " ORDER BY"
              . " $table_elecoa_grades.itemid";
        }
        else {
            $sql .=
            " ORDER BY"
              . " $table_elecoa_grades.attempt DESC";
        }

        $grades = $DB->get_records_sql($sql);

        foreach ($grades as $grade) {
            $grade->children = get_user_grade_records($userid, $elecoaid, $grade->attempt, $grade->itemid);
            $details = get_grade_details($elecoaid, $userid, $grade->attempt, $grade->itemid);
            if (count($details) > 0) {
                $grade->details = array_values($details);
            } else {
                $grade->details = array();
            }
        }

        return $grades;

    } catch (Exception $e) {
        echo var_export($e, true);
        return array();
    }
}

function get_grade_details($elecoaid, $userid, $attempt, $itemid) {
    global $DB;

    $table_elecoa = '{' . ELECOA_TABLE . '}';
    $table_elecoa_grades = '{' . ELECOA_GRADES_TABLE . '}';
    $table_elecoa_items = '{' . ELECOA_ITEMS_TABLE . '}';

    try {
        $sql =
            "SELECT"
              . " $table_elecoa_grades.*"
              . ", $table_elecoa_items.title"
              . ", $table_elecoa_items.identifier"
          . " FROM"
              . " $table_elecoa_grades"
              . " INNER JOIN $table_elecoa ON $table_elecoa_grades.elecoaid = $table_elecoa.id"
              . " INNER JOIN $table_elecoa_items ON $table_elecoa_grades.itemid = $table_elecoa_items.id"
          . " WHERE"
              . " $table_elecoa_grades.elecoaid = $elecoaid"
              . " AND $table_elecoa_grades.userid = $userid"
              . " AND $table_elecoa_grades.attempt = $attempt"
              . " AND $table_elecoa_grades.itemid = $itemid"
              . " AND $table_elecoa_grades.parentid = $itemid"
              . " AND $table_elecoa_grades.counter IS NOT NULL"
          . " ORDER BY"
              . " $table_elecoa_grades.counter DESC";

        return $DB->get_records_sql($sql);

    } catch (Exception $e) {
        echo var_export($e, true);
        return array();
    }
}

/**
 * ユーザーレコードを取得する。
 *
 * @param integer $courseid
 * @param boolean $outputexception
 *
 * @return array ユーザーレコード配列
 */
function get_grade_users($courseid, $outputexception = false) {
    global $DB;

    try {
        $table_elecoa = '{' . ELECOA_TABLE . '}';
        $table_elecoa_grades = '{' . ELECOA_GRADES_TABLE . '}';
        $table_user = '{user}';

        $sql =
            "SELECT DISTINCT"
              . " $table_user.*"
          . " FROM"
              . " $table_elecoa_grades"
              . " INNER JOIN $table_elecoa ON $table_elecoa_grades.elecoaid = $table_elecoa.id"
              . " INNER JOIN $table_user ON $table_elecoa_grades.userid = $table_user.id"
          . " WHERE"
              . " $table_elecoa.course = $courseid"
          . " ORDER BY"
              . " $table_user.id";

        return $DB->get_records_sql($sql);
    }
    catch (Exception $e) {
        if ($outputexception) {
            echo var_export($e, true);
        }

        throw $e;
    }
}

/**
 * 教材レコードを取得する。
 *
 * @param integer $courseid
 * @param integer $userid ユーザーで絞り込む場合は指定する
 * @param boolean $outputexception
 *
 * @return array 教材レコード配列
 */
function get_grade_items($courseid, $userid = 0, $outputexception = false) {
    global $DB;

    try {
        $table_elecoa = '{' . ELECOA_TABLE . '}';
        $table_elecoa_grades = '{' . ELECOA_GRADES_TABLE . '}';
        $table_elecoa_items = '{' . ELECOA_ITEMS_TABLE . '}';
        $table_user = '{user}';

        $sql =
            "SELECT DISTINCT"
              . " $table_elecoa_items.*"
          . " FROM"
              . " $table_elecoa_grades"
              . " INNER JOIN $table_elecoa ON $table_elecoa_grades.elecoaid = $table_elecoa.id"
              . " INNER JOIN $table_elecoa_items ON $table_elecoa_grades.itemid = $table_elecoa_items.id"
          . " WHERE"
              . " $table_elecoa.course = $courseid"
              . " AND $table_elecoa_grades.parentid IS NULL";

        if ($userid) {
            $sql .= " AND $table_elecoa_grades.userid = $userid";
        }

        $sql .=
            " ORDER BY"
              . " $table_elecoa_items.id";

        return $DB->get_records_sql($sql);
    }
    catch (Exception $e) {
        if ($outputexception) {
            echo var_export($e, true);
        }

        throw $e;
    }
}

/**
 * ログレコードからトラッキング情報XMLのDOMDocumentオブジェクトを生成して返す。
 *
 * @param object $log ログレコードオブジェクト
 * @return DOMDocumentオブジェクト。データが取得できなかった場合はnull。
 */
function get_dom_from_log($log) {
    if (empty($log)) {
        return null;
    }
    if (empty($log->logvalue)) {
        return null;
    }

    if ($log->logkey == 'RTM') {
        $xml = "";
        $lines = explode("\n",$log->logvalue);
        foreach ($lines as $line)
        {
            $data = explode("=",$line);
            if ( $data[0]=="runtimeXML" ) {
                $xml = $data[1];
                break;
            }
        }
    } else {
        $xml = $log->logvalue;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    if (!$dom->loadXML(rawurldecode($xml))) {
        return null;
    }

    return $dom;
}

function cell($value, $class = null) {
    echo '<span class="cell' . (is_null($class) ? '">' : " $class\">");
    echo $value;
    echo '</span>';
}

function get_datetime_string($datetime_value) {
    if (!is_null($datetime_value)) {
        return userdate($datetime_value, '%Y-%m-%d %H:%M:%S');
    } else {
        return '';
    }
}

function get_score_string($score_value, $csv = false) {
    if (is_null($score_value)) {
        return '';
    } else {
        if ($csv) {
            return $score_value;
        } else {
            return round($score_value, 4);
        }
    }
}

/**
 * フルネームを取得する。
 *
 * @param string $lastname
 * @param string $firstname
 *
 * @return string フルネーム
 */
function get_fullname($lastname, $firstname) {
    $fake = new stdclass(); // fake user
    $fake->lastname = 'LLLL';
    $fake->firstname = 'FFFF';
    $fullname = get_string('fullnamedisplay', '', $fake);
    if (strpos($fullname, 'LLLL') < strpos($fullname, 'FFFF')) {
        return $lastname . ' ' . $firstname;
    } else {
        return $firstname . ' ' . $lastname;
    }
}

/**
 * 完了状態の数値を文字列に変換する。
 *
 * @param integer $completion_value
 * @return 完了状態文字列
 */
function get_completion_string($completion_value) {
    switch ($completion_value) {
        case 0:
            return get_grade_string('completed');

        case 1:
            return get_grade_string('incomplete');

        case 2:
            return get_grade_string('notattempted');

        default:
            return get_grade_string('unknown');
    }
}

/**
 * 合否の数値を文字列に変換する。
 *
 * @param integer $success_value
 * @return 合否文字列
 */
function get_success_string($success_value) {
    switch ($success_value) {
        case 0:
            return get_grade_string('satisfied');

        case 1:
            return get_grade_string('notsatisfied');

        default:
            return get_grade_string('unknown');
    }
}

/**
 * 時間の数値を文字列に変換する。
 *
 * @param integer $time_value
 * @return 時間文字列
 */
function get_time_string($time_value) {
    $seconds = floor($time_value % 60);
    $minutes = floor(floor($time_value / 60) % 60);
    $hours   = floor(floor($time_value / 60) / 60);

    return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);
}

/**
 * &userid=4&userid=5 のようなクエリー文字列から array('4', '5') のような配列を返す。
 *
 * @param string $name パラメータ文字列
 * @param mixed $default 取得できなかった場合のデフォルト値
 * @return 配列
 */
function get_numeric_array_parameter($name, $default) {
    if (!preg_match_all('/[&\?]' . $name . '=([0-9]+)/', $_SERVER['QUERY_STRING'], $matches)) {
        return $default;
    }

    return $matches[1];
}

function show_options($value_options, $conditions, $param) {
    $selected = $conditions[$param];
    foreach ($value_options as $value => $option) {
        echo '<option value="', htmlspecialchars($value), ($selected === $value) ? '" selected="selected">' : '">', htmlspecialchars($option), '</option>';
    }
}

function show_input_value($conditions, $param, $type) {
    if ($type == PARAM_FLOAT) {
        return $conditions[$param] >= 0.0 ? $conditions[$param] : '';
    }
    if ($type == PARAM_INT) {
        return $conditions[$param] >= 0 ? $conditions[$param] : '';
    }
    return $conditions[$param];
}

/**
 * ローカライズされた文字列を取得する。
 *
 * @param string $key キー文字列
 * @param boolean $escape HTMLエスケープをするかどうか
 *
 * @return ローカライズされた文字列
 */
function get_grade_string($key, $escape = true) {
    $string = get_string($key, ELECOA_BLOCK_NAME);
    if ($escape) {
        $string = htmlspecialchars($string);
    }
    return $string;
}
