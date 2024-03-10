<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/elecoa/backup/moodle2/backup_elecoa_stepslib.php');

class backup_elecoa_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_elecoa_activity_structure_step('elecoa_structure', 'elecoa.xml'));
    }

    static public function encode_content_links($content) {
        return $content;
    }

}
