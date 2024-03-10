<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/elecoa/backup/moodle2/restore_elecoa_stepslib.php');

class restore_elecoa_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_elecoa_activity_structure_step('elecoa_structure', 'elecoa.xml'));
    }

    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('elecoa', array('intro'), 'elecoa');

        return $contents;
    }

    static public function define_decode_rules() {
        $rules = array();

        return $rules;
    }

    static public function define_restore_log_rules() {
        $rules = array();

        return $rules;
    }

    static public function define_restore_log_rules_for_course() {
        $rules = array();

        return $rules;
    }

}
