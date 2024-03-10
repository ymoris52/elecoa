<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__) . '/core/init_www.php');


$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record('course', array('id' => $id))) {
    error('Course ID is incorrect');
}

require_course_login($course);

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_elecoa\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

/// Print the header

$PAGE->set_url('/mod/'.ELECOA_MODULE.'/view.php', array('id' => $id));
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->shortname);

echo $OUTPUT->header();

/// Get all the appropriate data

if (! $elecoas = get_all_instances_in_course(ELECOA_MODULE, $course)) {
    echo $OUTPUT->heading(get_string('noelecoas', ELECOA_MODULE), 2);
    echo $OUTPUT->continue_button("../../course/view.php?id=$course->id");
    echo $OUTPUT->footer();
    die();
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');


$table = new html_table();
$table->rowclasses = array();


if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($elecoas as $elecoa) {
    if (!$elecoa->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$elecoa->coursemodule.'">'.format_string($elecoa->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$elecoa->coursemodule.'">'.format_string($elecoa->name).'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($elecoa->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', ELECOA_MODULE), 2);

echo html_writer::table($table);

/// Finish the page

echo $OUTPUT->footer();
