<?php

class backup_elecoa_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $elecoa = new backup_nested_element('elecoa', array('id'), array(
            'coursemodule',
            'name',
            'pkgtype',
            'reference',
            'intro',
            'introformat',
            'version',
            'timeopen',
            'timeclose',
            'whatgrade',
            'sha1hash',
            'timecreated',
            'timemodified')
        );
        $logs = new backup_nested_element('logs');
        $log = new backup_nested_element('log', array('id'), array(
            'userid',
            'scope',
            'attempt',
            'name',
            'counter',
            'type',
            'logkey',
            'logvalue',
            'timestamp')
        );
        $items = new backup_nested_element('items');
        $item = new backup_nested_element('item', array('id'), array(
            'parentid',
            'identifier',
            'title',
            'cotype')
        );
        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', array('id'), array(
                  'parentid',
                  'itemid',
                  'userid',
                  'attempt',
                  'counter',
                  'completion',
                  'success',
                  'score',
                  'lessontime',
                  'lessonperiod',
                  'totalperiod')
        );

        // Build the tree
        $elecoa->add_child($logs);
        $logs->add_child($log);
        $elecoa->add_child($items);
        $items->add_child($item);
        $elecoa->add_child($grades);
        $grades->add_child($grade);

        // Define sources
        $elecoa->set_source_table('elecoa', array('id' => backup::VAR_ACTIVITYID));
        $item->set_source_sql('
            SELECT *
            FROM {elecoa_items}
            WHERE elecoaid = :elecoa
            ORDER BY id',
            array('elecoa' => backup::VAR_PARENTID));
        if ($userinfo) {
            $log->set_source_sql('
                SELECT *
                FROM {elecoa_logs}
                WHERE elecoaid = :elecoa
                ORDER BY id',
                array('elecoa' => backup::VAR_PARENTID));
            $grade->set_source_sql('
                SELECT *
                FROM {elecoa_grades}
                WHERE elecoaid = :elecoa
                ORDER BY id',
                array('elecoa' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $log->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'userid');

        // Define file annotations
        $elecoa->annotate_files('mod_elecoa', 'intro', null);
        $elecoa->annotate_files('mod_elecoa', 'content', null);
        $elecoa->annotate_files('mod_elecoa', 'package', null);

        // Return the root element, wrapped into standard activity structure
        return $this->prepare_activity_structure($elecoa);

    }

}
