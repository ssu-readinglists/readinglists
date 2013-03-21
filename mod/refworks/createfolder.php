<?php
/**
 * Front screen of module (duplicate of index.php)
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */
require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/refworks_display.php');
require_once($CFG->libdir.'/formslib.php');
refworks_base::init();

//write header
$curbreadcrumb = array(array('name' => get_string('create_folder', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);
////////////////////////////////////////////
class mod_form_createfolder extends moodleform {

    public $_formref;

    function definition() {

        global $COURSE;
        $mform =& $this->_form;
        $this->_formref = $this->_form;
//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed

    /// Adding the standard "name" field
        $mform->addElement('text', 'namey', get_string('new_folder_name', 'refworks'), array('size'=>'64'));
        $mform->setType('namey', PARAM_TEXT);
        $mform->addRule('namey', null, 'required');
        $mform->addRule('namey', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons(false,get_string('create_folder','refworks'));
    }
}
////////////////////////////////////////
refworks_connect::require_login();
$mform = new mod_form_createfolder();
if ($fromform=$mform->get_data()) {
    //check session key if form actioned
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }
        //call the api function
    $newfoldername = $fromform->namey;
    $newfolder = false;
    if (refworks_connect::$connectok) {
        $newfolder = refworks_folder_api::create_folder(stripslashes($newfoldername));
    }
}
refworks_base::write_sidemenu();
refworks_base::write_heading(get_string('create_folder', 'refworks'));
if (!refworks_base::check_capabilities('mod/refworks:folders')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('create_folder','refworks')));
    refworks_base::write_footer();
    exit;
}
refworks_connect::write_login_errors();
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}
if ($fromform=$mform->get_data()) {
    //check session key if form actioned
     //enter feedback here
     if ($newfolder==false) {
         refworks_base::write_error(get_string('created_folder_error','refworks'));
     } else {
         notify(get_string('created_folder','refworks').' ('.htmlspecialchars(refworks_base::return_foldername($newfoldername)).')','notifysuccess');
     }
} else {//Default view
    ?>
    <div class="instructions"><p>
    This form allows you to create a new folder in your library. You can access your folders from the <em>Folders</em> section of the side-menu.
    </p></div>
    <?php
    if (refworks_base::$isinstance) {
        $form=$mform->_formref;
        $form->addElement('hidden', 'id', refworks_base::$cm->id);
    }
    $mform->display();
}
refworks_base::write_footer();
?>