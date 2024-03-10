<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(__FILE__)) . '/lib.php');

$logs = array(
    array('module' => ELECOA_MODULE, 'action' => 'add', 'mtable' => ELECOA_TABLE, 'field' => 'name'),
    array('module' => ELECOA_MODULE, 'action' => 'update', 'mtable' => ELECOA_TABLE, 'field' => 'name'),
    array('module' => ELECOA_MODULE, 'action' => 'view', 'mtable' => ELECOA_TABLE, 'field' => 'name'),
    array('module' => ELECOA_MODULE, 'action' => 'view all', 'mtable' => ELECOA_TABLE, 'field' => 'name')
);
