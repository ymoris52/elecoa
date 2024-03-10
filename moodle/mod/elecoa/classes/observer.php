<?php

namespace mod_elecoa;
defined('MOODLE_INTERNAL') || die();

class observer {

    public static function elecoa_user_deleted($e) {
//        global $DB;

//        $params = array('userid' => $e->relateduserid);
//        $DB->delete_records_select('elecoa_logs', "userid = :userid", $params);
//        $columns = $DB->get_columns('elecoa_grades');
//        if (!empty($columns)) {
//            $DB->delete_records_select('elecoa_grades', "userid = :userid", $params);
//        }
    }

    public static function elecoa_user_enrolment_deleted($e) {
        global $DB;

        if ($e->other['userenrolment']['lastenrol']) {
            $params = array('userid' => $e->other['userenrolment']['userid'], 'courseid' => $e->courseid);
            $inselect = "IN (SELECT e.id FROM {elecoa} e WHERE e.course = :courseid)";

            $DB->delete_records_select('elecoa_logs', "userid = :userid AND scope <> 1 AND elecoaid $inselect", $params);
            $columns = $DB->get_columns('elecoa_grades');
            if (!empty($columns)) {
                $DB->delete_records_select('elecoa_grades', "userid = :userid AND elecoaid $inselect", $params);
            }
        }
    }

}
