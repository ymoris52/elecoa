<?php
$module_dir = dirname(dirname(__FILE__));
$moodle_dir = dirname(dirname($module_dir));

spl_autoload_register(function ($classname) {
    $classfile = dirname(dirname(__FILE__)) . '/co/' . $classname . '.php';
    if (file_exists($classfile)) {
        require_once($classfile);
    }
});

if (defined('INIT_WWW_CUSTOM_SETUP')) {
    require_once($module_dir . '/core/contextLib.php');  // placed here to solve the problem __PHP_Incomplete_Class.
    if (INIT_WWW_CUSTOM_SETUP) {
        define('ABORT_AFTER_CONFIG', TRUE);
        require_once($moodle_dir . '/config.php');
        require_once($module_dir . '/core/setup.php');
    } else {
        require_once($moodle_dir . '/config.php');
    }
    require_once($module_dir . '/lib.php');
    require_once($module_dir . '/locallib.php');
    require_once($module_dir . '/core/xmlLib.php');
    require_once($module_dir . '/core/sessionlib.php');
} else if (defined('INIT_WWW_MINIMUM')) {
    // do nothing
} else {
    require_once($module_dir . '/core/contextLib.php');  // placed here to solve the problem __PHP_Incomplete_Class.
    require_once($moodle_dir . '/config.php');
    require_once($module_dir . '/lib.php');
    require_once($module_dir . '/locallib.php');
    require_once($module_dir . '/core/xmlLib.php');
    require_once($module_dir . '/core/sessionlib.php');
}
require_once($module_dir . '/core/utilityLib.php');
require_once($module_dir . '/core/coLib.php');
require_once($module_dir . '/core/Platform.php');
require_once($module_dir . '/core/CommandEntry.php');

define('log_module', 'MoodleLog');
define('log_path', '');

require_once($module_dir . '/core/' . log_module . '.php');
require_once($module_dir . '/core/MoodleGrade.php');
require_once($module_dir . '/core/CMI5Config.php');
require_once($module_dir . '/core/CMI5Extension.php');

set_error_handler('error_handler');

function createAPIAdapterProvider($type) {
    $class_name = $type . 'APIAdapterProvider';
    return new $class_name();
}

function getLogModule() {
    $LogModule = log_module;
    return new $LogModule(log_path, ELECOA_LOGS_TABLE, ELECOA_ID_COLUMN); //log_path is defined in init_www.php
}

function readLog($ctx, $activity_id, $count, $type, $keys, $global_to_system = FALSE) {
    $log = getLogModule();
    return $log->readLog($ctx, $activity_id, $count, $type, $keys, $global_to_system);
}

function writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system = FALSE) {
    $log = getLogModule();
    return $log->writeLog($ctx, $activity_id, $count, $type, $key_value_pairs, $global_to_system);
}

function getGradeModule() {
    return new MoodleGrade(ELECOA_TABLE, ELECOA_ITEMS_TABLE, ELECOA_GRADES_TABLE, ELECOA_ID_COLUMN, 'elecoa_update_grades');
}

function error_handler($errno, $errstr, $errfile, $errline) {
    if ( E_RECOVERABLE_ERROR === $errno ) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
    return FALSE;
}
