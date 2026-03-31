<?php
$observers = array(
    array(
        'eventname' => 'core\event\user_enrolment_created',
        'callback'  => 'local_edwiserbridge_observer::user_enrolment_created',
    ),
    array(
       'eventname' => 'core\event\user_enrolment_deleted',
       'callback'  => 'local_edwiserbridge_observer::user_enrolment_deleted',
    ),
    array(
       'eventname' => 'core\event\user_created',
       'callback'  => 'local_edwiserbridge_observer::user_created',
    ),
    array(
       'eventname' => 'core\event\user_deleted',
       'callback'  => 'local_edwiserbridge_observer::user_deleted',
    ),
    array(
       'eventname' => 'core\event\user_updated',
       'callback'  => 'local_edwiserbridge_observer::user_updated',
    ),
    /*array(
       'eventname' => 'core\event\user_password_updated',
       'callback'  => 'local_edwiserbridge_observer::user_password_updated',
    ),*/
    array(
       'eventname' => 'core\event\course_deleted',
       'callback'  => 'local_edwiserbridge_observer::course_deleted',
    )
);
