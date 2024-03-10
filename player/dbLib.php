<?php

$DB;

$CFG = new stdClass();
$CFG->dsn = '';
$CFG->dbuser = '';
$CFG->dbpass = '';

setup_DB();

function setup_DB() {
    global $DB, $CFG;

    $DB = new DbObject(NULL);
}

class DbObject {
    private $dbh;

    function __construct($db) {
        $this->dbh = $db;
    }

    function __call($name, $args) {
        if ($name === 'get_record') {
            return null;
        }
        if ($name === 'get_records_sql') {
            return array();
        }
        if ($name === 'insert_record') {
        }
        if ($name === 'update_record') {
        }
        if ($name === 'delete_records') {
        }
        return;
    }
}
