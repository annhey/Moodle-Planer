/* @package    mod_scheduler
 * @author     CiL RWTH Aachen, Anna Heynkes
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var modal = document.getElementById("student_comment_window");
var span = document.getElementsByClassName("close")[0];

/*  
 * Function opens the modal student message window onclick, changes its CSS and adds slot information necessary for booking
 */ 
function openCommentField(slotid){
    //Open the modal
        modal.style.display = "block";
    //Adjust CSS
        //texteditor
        document.getElementsByClassName("fitemtitle")[0].style.display = "none";
        document.getElementsByClassName("felement")[0].style.marginLeft = "0";
        //drag-and-drop (filemanager)
        document.getElementsByClassName("fitemtitle")[1].style.display = "none";
        document.getElementsByClassName("felement ffilemanager")[0].style.marginLeft = "0";
    //Add slot ID to the form and tell studentview.controller to book the slot
        document.getElementById("divslotid").innerHTML = '<input name="slotid" value="' + slotid + '" type="hidden"><input name="what" value="bookslot" type="hidden">';        
}

/*
 * Function closes the modal student message window
 */
function closeCommentField() {
    modal.style.display = "none";
    
}