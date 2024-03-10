<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\user_deleted',
        'callback' => '\mod_elecoa\observer::elecoa_user_deleted',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => '\mod_elecoa\observer::elecoa_user_enrolment_deleted',
    )
);
