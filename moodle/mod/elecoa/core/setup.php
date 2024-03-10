<?php

global $CFG;

if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', TRUE);
}

global $DB;
global $SESSION;
global $USER;
global $SITE;
global $PAGE;
global $COURSE;
global $OUTPUT;
global $FULLME;
global $ME;
global $FULLSCRIPT;
global $SCRIPT;

require_once($CFG->libdir .'/setuplib.php');        // Functions that MUST be loaded first

// Increase memory limits if possible
raise_memory_limit(MEMORY_STANDARD);

// Time to start counting
init_performance_info();

if (!isset($CFG->prefix)) {   // Just in case it isn't defined in config.php
    $CFG->prefix = '';
}

// location of all languages except core English pack
if (!isset($CFG->langotherroot)) {
    $CFG->langotherroot = $CFG->dataroot.'/lang';
}

// location of local lang pack customisations (dirs with _local suffix)
if (!isset($CFG->langlocalroot)) {
    $CFG->langlocalroot = $CFG->dataroot.'/lang';
}

//point pear include path to moodles lib/pear so that includes and requires will search there for files before anywhere else
//the problem is that we need specific version of quickforms and hacked excel files :-(
ini_set('include_path', $CFG->libdir.'/pear' . PATH_SEPARATOR . ini_get('include_path'));
//point zend include path to moodles lib/zend so that includes and requires will search there for files before anywhere else
//please note zend library is supposed to be used only from web service protocol classes, it may be removed in future
ini_set('include_path', $CFG->libdir.'/zend' . PATH_SEPARATOR . ini_get('include_path'));

if (file_exists($CFG->libdir . '/classes/component.php')) {
    // Register our classloader, in theory somebody might want to replace it to load other hacked core classes.
    if (defined('COMPONENT_CLASSLOADER')) {
        spl_autoload_register(COMPONENT_CLASSLOADER);
    } else {
        spl_autoload_register('core_component::classloader');
    }
}

// Load up standard libraries
//require_once($CFG->libdir .'/filterlib.php');       // Functions for filtering test as it is output
//require_once($CFG->libdir .'/ajax/ajaxlib.php');    // Functions for managing our use of JavaScript and YUI
require_once($CFG->libdir .'/weblib.php');          // Functions relating to HTTP and content
require_once($CFG->libdir .'/outputlib.php');       // Functions for generating output
require_once($CFG->libdir .'/navigationlib.php');   // Class for generating Navigation structure
require_once($CFG->libdir .'/dmllib.php');          // Database access
require_once($CFG->libdir .'/datalib.php');         // Legacy lib with a big-mix of functions.
require_once($CFG->libdir .'/accesslib.php');       // Access control functions
//require_once($CFG->libdir .'/deprecatedlib.php');   // Deprecated functions included for backward compatibility
require_once($CFG->libdir .'/moodlelib.php');       // Other general-purpose functions
//require_once($CFG->libdir .'/enrollib.php');        // Enrolment related functions
require_once($CFG->libdir .'/pagelib.php');         // Library that defines the moodle_page class, used for $PAGE
require_once($CFG->libdir .'/blocklib.php');        // Library for controlling blocks
//require_once($CFG->libdir .'/grouplib.php');        // Groups functions
require_once($CFG->libdir .'/sessionlib.php');      // All session and cookie related stuff
//require_once($CFG->libdir .'/editorlib.php');       // All text editor related functions and classes
//require_once($CFG->libdir .'/messagelib.php');      // Messagelib functions
require_once($CFG->libdir .'/modinfolib.php');      // Cached information on course-module instances
require_once($CFG->dirroot.'/cache/lib.php');       // Cache API

// Connect to the database
setup_DB();

// Load up any configuration from the config table
initialise_cfg();
//$CFG->dbsessions = true;
//$CFG->sessioncookie = '';
//$CFG->sessioncookiedomain = '';
//$CFG->sessioncookiepath = '';

if (file_exists($CFG->libdir . '/classes/shutdown_manager.php')) {
    core_shutdown_manager::initialize();
}

// Calculate and set $CFG->ostype to be used everywhere. Possible values are:
// - WINDOWS: for any Windows flavour.
// - UNIX: for the rest
// Also, $CFG->os can continue being used if more specialization is required
if (stristr(PHP_OS, 'win') && !stristr(PHP_OS, 'darwin')) {
    $CFG->ostype = 'WINDOWS';
} else {
    $CFG->ostype = 'UNIX';
}
$CFG->os = PHP_OS;

// Work around for a PHP bug   see MDL-11237
ini_set('pcre.backtrack_limit', 20971520);  // 20 MB

define('NO_MOODLE_COOKIES', FALSE);

// start session and prepare global $SESSION, $USER
if (empty($CFG->sessiontimeout)) {
    $CFG->sessiontimeout = 7200;
}
\core\session\manager::start();
$SESSION = &$_SESSION['SESSION'];
$USER    = &$_SESSION['USER'];
