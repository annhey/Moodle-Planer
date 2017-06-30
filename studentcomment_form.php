<?php

/* 
 * This class represents an appointment booking form that allows students to attach a message and files.
 *
 * @package    mod_scheduler
 * @author     CiL RWTH Aachen, Anna Heynkes
 * @licence    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class scheduler_studentcomment_form extends moodleform {
    
    private $noteoptions;
    
    public function definition() {
        
        global $CFG;
        $this->context = context_system::instance();
        
        $mform = $this->_form;
        
        // add a headline with collapsed/extandable texteditor and drag-and-drop area
        $mform->addElement('header', 'bookwithmessagetitle', get_string('titleforbookwithmessage', 'scheduler')); 
        $mform->setExpanded('bookwithmessagetitle', false); // collapses texteditor and drag-and-drop area
        
        // pass id to the form
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        // add a texteditor
        $this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                             'context' => $this->context, 'subdirs' => false);
        
        $mform->setType('editor', PARAM_NOTAGS);
        $mform->setType('id', PARAM_RAW);
        $mform->setType('contextid', PARAM_RAW);
        $mform->addElement('editor', 'notes_editor', get_string('comments', 'scheduler'),
                           array('rows' => 3, 'columns' => 60), $this->noteoptions);
        //$mform->addHelpButton('notes_editor', 'notes_editor', 'scheduler');
        
        // add a filemanager for drag-and-drop file upload
        $this->fileoptions = array('subdirs' => 0, 'maxbytes' => 0, 'areamaxbytes' => 10485760, 'maxfiles' => 50,
                             'accepted_types' => array('image', 'document', 'application/pdf', 'application/zip', 'presentation', 'application/vnd.openxmlformats-officedocument.presentationml.template'), 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL);
        $mform->addElement('filemanager', 'attachment', get_string('attachment', 'scheduler'), null, $this->fileoptions);
        
        // add slotid
        $mform->addElement('html', '<div id="divslotid"></div>');
        
        // add input that will signal to studentview.controller.php that the booking was done with a message form
        $mform->addElement('html', '<input name="bookwithmessage" value="yes" type="hidden">');
        
        // add submit and cancel buttons
        $this->add_action_buttons($cancel = true, get_string('sendmessageandbook', 'scheduler'));
        
    }
    
    function display() {
        $this->_form->display();
    }
    
}