<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__FILE__)) . '/lib.php');

/**
 * xmldb_elecoa_upgrade
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_elecoa_upgrade($oldversion) {

    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2013112601) {

        $table = new xmldb_table('elecoa_logs');
        $index = new xmldb_index('elecoaid-userid-attempt-name', XMLDB_INDEX_NOTUNIQUE, array('elecoaid', 'userid', 'attempt', 'name'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(TRUE, 2014020100, 'elecoa');
    }

    if ($oldversion < 2017120101) {

        $table = new xmldb_table('elecoa_registration');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('uid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('registration', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('cid-uid', XMLDB_INDEX_UNIQUE, array('cid', 'uid'));
        $table->add_index('registration', XMLDB_INDEX_UNIQUE, array('registration'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(TRUE, 2017120101, 'elecoa');
    }

    if ($oldversion < 2018020104) {

        $table = new xmldb_table('elecoa_authtoken');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('registration', XMLDB_TYPE_CHAR, '36', null, XMLDB_NOTNULL, null, null);
        $table->add_field('activity', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('genkey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fetched', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valid', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(TRUE, 2018020104, 'elecoa');
    }

    if ($oldversion < 2018042300) {

        $table = new xmldb_table('elecoa_grades');

        $field_lessonperiod = new xmldb_field('lessonperiod', XMLDB_TYPE_NUMBER, '10,2', XMLDB_UNSIGNED, null, null, null, 'lessontime');
        $field_totalperiod = new xmldb_field('totalperiod', XMLDB_TYPE_NUMBER, '10,2', XMLDB_UNSIGNED, null, null, null, 'lessonperiod');
        //$name, $type, $precision, $unsigned, $notnull, $sequence, $default, $previous

        $dbman->change_field_type($table, $field_lessonperiod);
        $dbman->change_field_type($table, $field_totalperiod);

        upgrade_mod_savepoint(true, 2018042300, 'elecoa');
    }

    if ($oldversion < 2019022800) {

        $table = new xmldb_table('elecoa_authtoken');
        $field = new xmldb_field('attempt', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 1, 'activity');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(TRUE, 2019022800, 'elecoa');
    }

    if ($oldversion < 2019031501) {

        $table = new xmldb_table('elecoa_authtoken');
        $field = new xmldb_field('user', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'authuser');
        }

        upgrade_mod_savepoint(TRUE, 2019031501, 'elecoa');
    }

    if ($oldversion < 2019080102) {

        $table = new xmldb_table('elecoa_authtoken');
        $field_sessionid = new xmldb_field('sessionid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'attempt');
        $field_title = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'activity');

        if (!$dbman->field_exists($table, $field_sessionid)) {
            $dbman->add_field($table, $field_sessionid);
        }
        if (!$dbman->field_exists($table, $field_title)) {
            $dbman->add_field($table, $field_title);
        }

        upgrade_mod_savepoint(TRUE, 2019080102, 'elecoa');
    }

    return TRUE;
}
