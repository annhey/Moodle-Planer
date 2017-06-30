<?php

/**
 * Controller for student view
 *
 * @package    mod_scheduler
 * @authors    2015 Henning Bostelmann and others (see README.txt)
 * @lastmodified 2017 CiL RWTH Aachen, Anna Heynkes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');

$returnurl = new moodle_url('/mod/scheduler/view.php', array('id' => $cm->id));

/************************************************ Book a slot  ************************************************/

if ($action == 'bookslot' ) {

    require_sesskey();
    require_capability('mod/scheduler:appoint', $context);
    
    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);
    
    
    if (!$slot) {
        throw new moodle_exception('error');
    }

    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $requiredcapacity = 1;
    $userstobook = array($USER->id);
    if ($appointgroup) {
        $groupmembers = $scheduler->get_available_students($appointgroup);
        $requiredcapacity = count($groupmembers);
        $userstobook = array_keys($groupmembers);
    }

    $errormessage = '';

    $bookinglimit = $scheduler->count_bookable_appointments($USER->id, false);
    if ($bookinglimit == 0) {
        $errormessage = get_string('selectedtoomany', 'scheduler', $bookinglimit);
    }
    if (!$errormessage) {
        // Validate our user ids.
        $existingstudents = array();
        foreach ($slot->get_appointments() as $app) {
            $existingstudents[] = $app->studentid;
        }
        $userstobook = array_diff($userstobook, $existingstudents);

        $remaining = $slot->count_remaining_appointments();
        // If the slot is already overcrowded...
        if ($remaining >= 0 && $remaining < $requiredcapacity) {
            if ($requiredcapacity > 1) {
                $errormessage = get_string('notenoughplaces', 'scheduler');
            } else {
                $errormessage = get_string('slot_is_just_in_use', 'scheduler');
            }
        }
    }

    if ($errormessage) {
        echo $output->header();
        echo $output->box($errormessage, 'error');
        echo $output->continue_button($returnurl);
        echo $output->footer();
        exit();
    }
    
    $bookwithmessage = optional_param('bookwithmessage', 'no', PARAM_TEXT);
    
    if ($bookwithmessage === 'no') {
        
        // Create new appointment and add it for each member of the group.
        foreach ($userstobook as $studentid) {
            $appointment = $slot->create_appointment();
            $appointment->studentid = $studentid;
            $appointment->attended = 0;
            $appointment->timecreated = time();
            $appointment->timemodified = time();

            \mod_scheduler\event\booking_added::create_from_slot($slot)->trigger();

            // Notify the teacher.
            if ($scheduler->allownotifications) {
                $student = $DB->get_record('user', array('id' => $appointment->studentid));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
                scheduler_messenger::send_slot_notification($slot, 'bookingnotification', 'applied',
                                                            $student, $teacher, $teacher, $student, $course);
            }        
        }
        $slot->save();
        redirect($returnurl);
    
    
    } else { // This code is reached if a message form has been submitted but the filemanager content has not been collected yet
        
        // save text message in session
        $editor = optional_param_array('notes_editor', null, PARAM_CLEANHTML);
        if ($editor != null && $editor != "") {
            $_SESSION['message'] = $editor['text'];
        }
        // save users to book and slot id in session
        $_SESSION['users'] = $userstobook;
        $_SESSION['myslotid'] = $slotid;
        
        // Now wait for an 'okay' signal from filemanager that all form data have been collected.
    }
        
}

// Book a slot with a student message and/or files attached
if (isset($_SESSION['okay']) && $_SESSION['okay'] == 'true') { // checks whether a message form has been submitted and the data from both texteditor and filemanager collected
          
    $studentmessage = '';
    $attachments = '';
    // get student's message
    if (isset($_SESSION['message'])) {
        $studentmessage = $_SESSION['message'];
        unset($_SESSION['message']);
    }
    // get files uploaded by student
    if (isset($_SESSION['fileUrl'])) {
        $url = array();
        $url = $_SESSION['fileUrl'];
        $count = 0;
        $lastentry = end($url);
        foreach ($url as $value) {         
            $link = $value->out();
            $link = "<a href='" . $link ."'>". get_string('attachment', 'scheduler') . "</a>";            
            if ($value != $lastentry) {
                $link .= ", ";
            }
            if ($count != 0) { // skip first entry (it's not the file link we want)
                $attachments .= $link;
            }
            $count++;
        }
        unset($_SESSION['fileUrl']);
    }
    // get slot and user info necessary for booking
    $userstobook = $_SESSION['users'];
    $slotid = $_SESSION['myslotid'];
    $slot = $scheduler->get_slot($slotid);
    unset($_SESSION['users']);
    unset($_SESSION['myslotid']);
    unset($_SESSION['okay']);
        
    // Create new appointment and add it for each member of the group.
    foreach ($userstobook as $studentid) {
        $appointment = $slot->create_appointment();
        $appointment->studentid = $studentid;
        $appointment->attended = 0;
        $appointment->studentcomment = $studentmessage . $attachments;
        $appointment->timecreated = time();
        $appointment->timemodified = time();

        \mod_scheduler\event\booking_added::create_from_slot($slot)->trigger();

        // Notify the teacher.
        if ($scheduler->allownotifications) {
            $student = $DB->get_record('user', array('id' => $appointment->studentid));
            $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
            scheduler_messenger::send_slot_notification($slot, 'bookingnotification', 'applied',
                                                        $student, $teacher, $teacher, $student, $course);
            }        
        }
        $slot->save();
        redirect($returnurl);
           
    }


/******************************** Cancel a booking (for the current student or a group) ******************************/

if ($action == 'cancelbooking') {

    require_sesskey();
    require_capability('mod/scheduler:appoint', $context);

    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    }

    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $userstocancel = array($USER->id);
    if ($appointgroup) {
        $userstocancel = array_keys($scheduler->get_available_students($appointgroup));
    }

    foreach ($userstocancel as $userid) {
        if ($appointment = $slot->get_student_appointment($userid)) {
            $scheduler->delete_appointment($appointment->id);

            // Notify the teacher.
            if ($scheduler->allownotifications) {
                $student = $DB->get_record('user', array('id' => $USER->id));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
                scheduler_messenger::send_slot_notification($slot, 'bookingnotification', 'cancelled',
                                                            $student, $teacher, $teacher, $student, $COURSE);
            }
            \mod_scheduler\event\booking_removed::create_from_slot($slot)->trigger();
        }
    }
    redirect($returnurl);

}