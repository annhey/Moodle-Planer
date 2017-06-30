<?php
/* 
 * Collection of functions related to the autoscheduling of students who have yet to make an appointment
 * in a particular scheduler instance. The functions are called in teacherview.php.
 * 
 * @author      CiL RWTH Aachen, Anna Heynkes
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
 * This function receives an array of slot or student objects as parameter
 * and returns an array of their ids.
 */
function getIds($objects) {
    $idlist = array();
    foreach ($objects as $slotOrStudent) {
        $idlist[] = $slotOrStudent->id;
    }
    return $idlist;
}

/*
 * This function returns all students who are allowed to book an appointment
 * in this scheduler, but have not done so, yet.
 */
function getStudentsToBook($scheduler) {
    $result = array();
    $weCanBook = $scheduler->get_students_for_scheduling();  
    if (!$weCanBook) {
        return null;
    }       
    foreach($weCanBook as $student) {     
        $studid = $student->id;
        if(!$scheduler->get_appointments_for_student($studid)) {
           $result[] = $student;
        }        
    }
    return $result;
}

/*
 * This function checks whether there are students who should and could be automatically booked
 */
function readyForAutobook($studentsToBook, $availableslots) {
    if ($studentsToBook != null && $availableslots != null && sizeof($availableslots) >= sizeof($studentsToBook)) {
        return true;
    }
    return false;
}
