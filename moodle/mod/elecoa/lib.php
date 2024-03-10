<?php

/**
 * Library of interface functions and constants for module elecoa
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the elecoa specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 */

defined('MOODLE_INTERNAL') || die();

if (!defined('ELECOA_TABLE'))        define('ELECOA_TABLE',        'elecoa');
if (!defined('ELECOA_LOGS_TABLE'))   define('ELECOA_LOGS_TABLE',   'elecoa_logs');
if (!defined('ELECOA_ITEMS_TABLE'))  define('ELECOA_ITEMS_TABLE',  'elecoa_items');
if (!defined('ELECOA_GRADES_TABLE')) define('ELECOA_GRADES_TABLE', 'elecoa_grades');
if (!defined('ELECOA_ID_COLUMN'))    define('ELECOA_ID_COLUMN',    'elecoaid');

define('ELECOA_MODULE', 'elecoa');
define('MOD_ELECOA_MODULE', 'mod_elecoa');

/**
 * If you for some reason need to use global variables instead of constants, do not forget to make them
 * global as this file can be included inside a function scope. However, using the global variables
 * at the module level is not a recommended.
 */
//global $ELECOA_GLOBAL_VARIABLE;
//$ELECOA_QUESTION_OF = array('Life', 'Universe', 'Everything');

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $elecoa An object from the form in mod_form.php
 * @param object $mform
 * @return int The id of the newly inserted elecoa record
 */
function elecoa_add_instance($elecoa, $mform=null) {
    global $DB;

    require_once(dirname(__FILE__) . '/locallib.php');

    if (empty($elecoa->timeopen)) {
        $elecoa->timeopen = 0;
    }

    if (empty($elecoa->timeclose)) {
        $elecoa->timeclose = 0;
    }

    if (!isset($elecoa->whatgrade)) {
        $elecoa->whatgrade = 0;
    }

    $elecoa->timecreated = time();

    # You may have to add extra stuff in here #

    $context = context_module::instance($elecoa->coursemodule);

    $id = $DB->insert_record(ELECOA_TABLE, $elecoa);

/// update course module record - from now on this instance properly exists and all function may be used
    $DB->set_field('course_modules', 'instance', $id, array('id'=>$elecoa->coursemodule));

/// reload elecoa instance
    $record = $DB->get_record(ELECOA_TABLE, array('id'=>$id));

/// store the package and verify
    if ($mform) {
        $filename = $mform->get_new_filename('packagefile');
        if ($filename !== FALSE) {
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, MOD_ELECOA_MODULE, 'package');
            $mform->save_stored_file('packagefile', $context->id, MOD_ELECOA_MODULE, 'package', 0, '/', $filename);
            $record->reference = $filename;
        }
    }

// save reference
    $DB->update_record(ELECOA_TABLE, $record);

/// extra fields required in grade related functions.
    $record->course     = $elecoa->course;
    $record->cmidnumber = $elecoa->cmidnumber;
    $record->cmid       = $elecoa->coursemodule;

    elecoa_parse($record, TRUE);

    elecoa_grade_item_update($record);

    return $record->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $elecoa An object from the form in mod_form.php
 * @param object $mform
 * @return boolean Success/Fail
 */
function elecoa_update_instance($elecoa, $mform=null) {
    global $CFG, $DB;

    require_once(dirname(__FILE__) . '/locallib.php');

    if (empty($elecoa->timeopen)) {
        $elecoa->timeopen = 0;
    }

    if (empty($elecoa->timeclose)) {
        $elecoa->timeclose = 0;
    }

    if (!isset($elecoa->whatgrade)) {
        $elecoa->whatgrade = 0;
    }

    $cmid = $elecoa->coursemodule;

    $elecoa->id = $elecoa->instance;

//    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    $context = context_module::instance($cmid);

    if ($mform) {
        $filename = $mform->get_new_filename('packagefile');
        if ($filename !== FALSE) {
            $elecoa->reference = $filename;
            $fs = get_file_storage();
            $fs->delete_area_files($context->id, MOD_ELECOA_MODULE, 'package');

            $mform->save_stored_file('packagefile', $context->id, MOD_ELECOA_MODULE, 'package', 0, '/', $filename);
        }
    }

    $elecoa->timemodified = time();

    $DB->update_record(ELECOA_TABLE, $elecoa);

    $record = $DB->get_record(ELECOA_TABLE, array('id'=>$elecoa->id));

    $old_hash = $record->sha1hash;
    $fs = get_file_storage();
    $packagefile = FALSE;

    if ($packagefile = $fs->get_file($context->id, MOD_ELECOA_MODULE, 'package', 0, '/', $elecoa->reference)) {
        $new_hash = $packagefile->get_contenthash();
    } else {
        $new_hash = null;
    }

    if($old_hash != $new_hash){
        $DB->delete_records(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id));
    }

/// extra fields required in grade related functions
    $record->cmidnumber = $elecoa->cmidnumber;
    $record->cmid       = $elecoa->coursemodule;

    elecoa_parse($record, FALSE);

    elecoa_grade_item_update($record);
    elecoa_update_grades($record);

    return TRUE;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function elecoa_delete_instance($id) {
    global $DB;

    if (! $elecoa = $DB->get_record(ELECOA_TABLE, array('id'=>$id))) {
        return FALSE;
    }

    $result = TRUE;

    $DB->delete_records(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id));
    $DB->delete_records(ELECOA_ITEMS_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id));
    $DB->delete_records(ELECOA_LOGS_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id));
    $DB->delete_records(ELECOA_TABLE, array('id'=>$elecoa->id));

    elecoa_grade_item_delete($elecoa);

    return $result;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 */
function elecoa_user_outline($course, $user, $mod, $elecoa) {
    $return = new stdClass;
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function elecoa_user_complete($course, $user, $mod, $elecoa) {
    return TRUE;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in elecoa activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function elecoa_print_recent_activity($course, $viewfullnames, $timestart) {
    return FALSE;  //  True if anything was printed, otherwise false
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function elecoa_cron () {
    return TRUE;
}

/**
 * Must return an array of users who are participants for a given instance
 * of elecoa. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $elecoaid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function elecoa_get_participants($elecoaid) {
    return FALSE;
}

/**
 * This function returns if a scale is being used by one elecoa
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $elecoaid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 */
function elecoa_scale_used($elecoaid, $scaleid) {
    global $DB;

    $return = FALSE;

    //$rec = $DB->get_record(ELECOA_MODULE, array("id" => "$elecoaid", "scale" => "-$scaleid"));
    //
    //if (!empty($rec) && !empty($scaleid)) {
    //    $return = TRUE;
    //}

    return $return;
}

/**
 * Checks if scale is being used by any instance of elecoa.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any elecoa
 */
function elecoa_scale_used_anywhere($scaleid) {
    return FALSE;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function elecoa_uninstall() {
    return TRUE;
}

/**
 * Serves elecoa content and packages
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function mod_elecoa_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return FALSE;
    }

    require_login($course, TRUE, $cm);

    $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;

    if ($filearea === 'content') {
        $revision = (int)array_shift($args); // prevents caching problems - ignored here
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/".MOD_ELECOA_MODULE."/content/0/$relativepath";
        // TODO: add any other access restrictions here if needed!

    } else if ($filearea === 'package') {
        if (!has_capability('moodle/course:manageactivities', $context)) {
            return FALSE;
        }
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/".MOD_ELECOA_MODULE."/package/0/$relativepath";
        $lifetime = 0; // no caching here

    } else {
        return FALSE;
    }

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return FALSE;
    }

    // finally send the file
    send_stored_file($file, $lifetime, 0, FALSE);
}

/**
 * Update/create grade item for given elecoa
 *
 * @global stdClass
 * @global object
 * @uses GRADE_TYPE_VALUE
 * @uses GRADE_TYPE_NONE
 * @param object $elecoa object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function elecoa_grade_item_update($elecoa, $grades=NULL) {
    global $CFG, $DB;

    require_once(dirname(__FILE__) . '/locallib.php');

    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    $params = array('itemname' => $elecoa->name);
    if (isset($elecoa->cmidnumber)) {
        $params['idnumber'] = $elecoa->cmidnumber;
    }

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = 100;
    $params['grademin']  = 0;
    $params['multfactor'] = 100;

    if ($grades  === 'reset') {
        $params['reset'] = TRUE;
        $grades = NULL;
    }

    return grade_update('mod/'.ELECOA_MODULE, $elecoa->course, 'mod', ELECOA_MODULE, $elecoa->id, 0, $grades, $params);
}

/**
 * Delete grade item for given elecoa
 *
 * @global stdClass
 * @param object $elecoa object
 * @return object grade_item
 */
function elecoa_grade_item_delete($elecoa) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/'.ELECOA_MODULE, $elecoa->course, 'mod', ELECOA_MODULE, $elecoa->id, 0, NULL, array('deleted'=>1));
}

/**
 * Update grades in central gradebook
 *
 * @global stdClass
 * @global object
 * @param object $elecoa
 * @param int $userid specific user only, 0 mean all
 * @param bool $nullifnone
 */
function elecoa_update_grades($elecoa, $userid=0, $nullifnone=TRUE) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    
    if ($grades = elecoa_get_user_grades($elecoa, $userid)) {
        elecoa_grade_item_update($elecoa, $grades);
    }
    else if ($userid and $nullifnone) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = NULL;
        elecoa_grade_item_update($elecoa, $grade);
    }
    else {
        elecoa_grade_item_update($elecoa);
    }
}


/**
 * Return grade for given user or all users.
 *
 * @global stdClass
 * @global object
 * @param int $elecoaid id of elecoa
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function elecoa_get_user_grades($elecoa, $userid=0) {
    global $CFG, $DB;

    require_once(dirname(__FILE__) . '/locallib.php');

    $grades = array();
    if (empty($userid)) {
        if ($scousers = $DB->get_records_sql('SELECT DISTINCT userid FROM {' . ELECOA_GRADES_TABLE . '} WHERE ' . ELECOA_ID_COLUMN . '= ? AND parentid IS NULL GROUP BY userid', array($elecoa->id))) {
            foreach ($scousers as $scouser) {
                $grades[$scouser->userid] = new stdClass();
                $grades[$scouser->userid]->id     = $scouser->userid;
                $grades[$scouser->userid]->userid = $scouser->userid;
                $grades[$scouser->userid]->rawgrade = elecoa_grade_user($elecoa, $scouser->userid);
            }
        } else {
            return FALSE;
        }

    } else {
        if ($DB->count_records_sql('SELECT DISTINCT userid FROM {' . ELECOA_GRADES_TABLE . '} WHERE ' . ELECOA_ID_COLUMN . ' = ? AND parentid IS NULL AND userid = ?', array($elecoa->id, $userid)) == 0) {
            return FALSE; //no attempt yet
        }
        $grades[$userid] = new stdClass();
        $grades[$userid]->id     = $userid;
        $grades[$userid]->userid = $userid;
        $grades[$userid]->rawgrade = elecoa_grade_user($elecoa, $userid);
    }

    return $grades;
}

function elecoa_grade_user($elecoa, $userid) {
    global $DB;

    $grade = null;

    switch ($elecoa->whatgrade) {
        case ELECOA_FIRSTATTEMPT:
            if ($record = $DB->get_record(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id, 'parentid'=>null, 'userid'=>$userid), 'min(attempt) as attempt')) {
                $grade = $DB->get_record(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id, 'parentid'=>null, 'userid'=>$userid, 'attempt'=>$record->attempt));
            }
            break;

        case ELECOA_LASTATTEMPT:
            if ($record = $DB->get_record(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id, 'parentid'=>null, 'userid'=>$userid), 'max(attempt) as attempt')) {
                $grade = $DB->get_record(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id, 'parentid'=>null, 'userid'=>$userid, 'attempt'=>$record->attempt));
            }
            break;

        case ELECOA_AVERAGEATTEMPT:
            $grade = $DB->get_record(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id, 'parentid'=>null, 'userid'=>$userid), 'avg(score) as score');
            break;

        case ELECOA_HIGHESTATTEMPT:
        default:
            $grade = $DB->get_record(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id, 'parentid'=>null, 'userid'=>$userid), 'max(score) as score');
            break;
    }

    if ($grade) {
        return $grade->score;
    } else {
        return null;
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the scorm.
 *
 * @param object $mform form passed by reference
 */
function elecoa_reset_course_form_definition(&$mform) {
    $mform->addElement('header', ELECOA_MODULE.'header', get_string('modulenameplural', ELECOA_MODULE));
    $mform->addElement('advcheckbox', 'reset_'.ELECOA_MODULE, get_string('deleteallattempts',ELECOA_MODULE));
}

/**
 * Course reset form defaults.
 *
 * @return array
 */
function elecoa_reset_course_form_defaults($course) {
    return array('reset_'.ELECOA_MODULE=>1);
}

/**
 * Removes all grades from gradebook
 *
 * @global stdClass
 * @global object
 * @param int $courseid
 * @param string optional type
 */
function elecoa_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $sql = "SELECT s.*, cm.idnumber as cmidnumber, s.course as courseid
              FROM {".ELECOA_TABLE."} s, {course_modules} cm, {modules} m
             WHERE m.name='".ELECOA_MODULE."' AND m.id=cm.module AND cm.instance=s.id AND s.course=?";

    if ($records = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($records as $record) {
            elecoa_grade_item_update($record, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * scorm attempts for course $data->courseid.
 *
 * @global stdClass
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function elecoa_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', ELECOA_MODULE);
    $status = array();

    $propertyname = 'reset_'.ELECOA_MODULE;
    if (!empty($data->$propertyname)) {
        $rs = $DB->get_records(ELECOA_TABLE, array('course'=>$data->courseid));

        foreach ($rs as $elecoa) {
            $DB->delete_records(ELECOA_GRADES_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id));
            $DB->delete_records(ELECOA_LOGS_TABLE, array(ELECOA_ID_COLUMN=>$elecoa->id));
        }

        // remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            elecoa_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallattempts', ELECOA_MODULE), 'error'=>FALSE);
    }

    // no dates to shift here

    return $status;
}


/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_COMPLETION_HAS_RULES
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function elecoa_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return FALSE;
        case FEATURE_GROUPINGS:               return FALSE;
        case FEATURE_MOD_INTRO:               return TRUE;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return FALSE;
        case FEATURE_COMPLETION_HAS_RULES:    return FALSE;
        case FEATURE_GRADE_HAS_GRADE:         return TRUE;
        case FEATURE_GRADE_OUTCOMES:          return FALSE;
        case FEATURE_BACKUP_MOODLE2:          return TRUE;
        case FEATURE_SHOW_DESCRIPTION:        return TRUE;

        default: return null;
    }
}

function elecoa_course_format_display($user,$course) {
    global $CFG, $DB, $PAGE, $OUTPUT;

    $strupdate = get_string('update');
//    $context = get_context_instance(CONTEXT_COURSE,$course->id);
    $context = context_course::instance($course->id);

    if ($elecoas = get_all_instances_in_course(ELECOA_MODULE, $course)) {
        // The module SCORM activity with the least id is the course
        $elecoa = current($elecoas);
        if (! $cm = get_coursemodule_from_instance(ELECOA_MODULE, $elecoa->id, $course->id)) {
            print_error('invalidcoursemodule');
        }

        if ($PAGE->user_is_editing()) {
            // update
            echo '<div class="mod-elecoa">';
            echo $OUTPUT->heading(get_string('elecoaformat',ELECOA_MODULE), 2, 'headingblock header outline');

            $colspan = '';
            $headertext = '<table width="100%"><tr><td class="title">'.get_string('name').': <b>'.format_string($elecoa->name).'</b>';
            if (has_capability('moodle/course:manageactivities', $context)) {
                // Display update icon
                $path = $CFG->wwwroot.'/course';
                $headertext .= '<span class="commands">'.
                        '<a title="'.$strupdate.'" href="'.$path.'/mod.php?update='.$cm->id.'&amp;sesskey='.sesskey().'">'.
                        '<img src="'.$OUTPUT->pix_url('t/edit') . '" class="iconsmall" alt="'.$strupdate.'" /></a></span>';
                $headertext .= '</td>';
                $colspan = ' colspan="2"';
            }
            $headertext .= '</td></tr><tr><td'.$colspan.'>'.get_string('summary').':<br />'.format_module_intro(ELECOA_MODULE, $elecoa, $elecoa->coursemodule).'</td></tr></table>';
            echo $OUTPUT->box($headertext,'generalbox');

            echo '</div>';
        } else {
            // view
            $url = new moodle_url('/mod/'.ELECOA_MODULE.'/view.php', array('id'=>$cm->id));
            redirect($url);
        }

    } else {
        if (has_capability('moodle/course:update', $context)) {
            // Create a new activity
            $url = new moodle_url('/course/mod.php', array('id'=>$course->id, 'section'=>'0', 'sesskey'=>sesskey(),'add'=>ELECOA_MODULE));
            redirect($url);
        } else {
            echo $OUTPUT->notification('Could not find a elecoa course here');
        }
    }
}

/**
*
* for participation report
*
**/

function elecoa_get_view_actions() {
    return array('view', 'view all');
}

function elecoa_get_post_actions() {
    return array('start');
}
