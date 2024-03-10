<?php

class restore_elecoa_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('elecoa', '/activity/elecoa');
        $paths[] = new restore_path_element('elecoa_item', '/activity/elecoa/items/item');
        if ($userinfo) {
            $paths[] = new restore_path_element('elecoa_log', '/activity/elecoa/logs/log');
            $paths[] = new restore_path_element('elecoa_grade', '/activity/elecoa/grades/grade');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_elecoa($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->coursemodule = $this->task->get_moduleid();

        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('elecoa', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_elecoa_log($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->elecoaid = $this->get_new_parentid('elecoa');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('elecoa_logs', $data);
    }

    protected function process_elecoa_item($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->elecoaid = $this->get_new_parentid('elecoa');
        if (!empty($data->parentid)) {
            $data->parentid = $this->get_mappingid('elecoa_item', $data->parentid);
        }

        $newitemid = $DB->insert_record('elecoa_items', $data);
        $this->set_mapping('elecoa_item', $oldid, $newitemid);
    }

    protected function process_elecoa_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->elecoaid = $this->get_new_parentid('elecoa');
        $data->itemid = $this->get_mappingid('elecoa_item', $data->itemid);
        if (!empty($data->parentid)) {
            $data->parentid = $this->get_mappingid('elecoa_item', $data->parentid);
        }
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('elecoa_grades', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_elecoa', 'intro', null);
        $this->add_related_files('mod_elecoa', 'content', null);
        $this->add_related_files('mod_elecoa', 'package', null);
    }

}
