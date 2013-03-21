<?php
/**
 * Add a reference (rid) a user folder
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */
require_once(dirname(__FILE__).'/refworks_base.php');

GLOBAL $CFG, $OUTPUT;
require_once($CFG->libdir.'/formslib.php');

class refworks_addreferences_form extends moodleform {

    public $_formref;
    function definition() {

        $mform    =& $this->_form;
        $this->_formref = $this->_form;

        //-------------------------------------------------------------------------------
        // Add General box
        //$mform->addElement('header', 'general', get_string('general', 'form'));

        $folderlist = array();
        for ($a=0, $max=count(refworks_folder_api::$folders); $a<$max; $a++) {
            $folderlist[refworks_folder_api::$folders[$a]['name']] = htmlspecialchars(refworks_base::return_foldername(refworks_folder_api::$folders[$a]['name'],true));
        }

        $mform->addElement('select', 'fld', get_string('select_folder', 'refworks'), $folderlist);

        $mform->addRule('fld', null, 'required');

        //-------------------------------------------------------------------------------
        // Add Save Changes and Cancel buttons
        $this->add_action_buttons(true,get_string('add_to_folder_title','refworks'));
    }
}
refworks_base::init();

$rid = required_param('rid', PARAM_INT);
$fld = optional_param('fld', '', PARAM_TEXT);
$fld = refworks_base::return_foldername($fld);
//work out where we cam from
$refer = optional_param('refer', '' , PARAM_URL);
if ($refer == '') {
    if (isset($_SERVER['HTTP_REFERER'])) {
        if (strpos($_SERVER['HTTP_REFERER'], 'viewrefs.php')!==false) {
            $refer = $_SERVER['HTTP_REFERER'];
        }
    }
}
if ($refer == '') {
    $refer = refworks_base::return_link('viewrefs.php');
}


$curbreadcrumb = array(array('name' => get_string('view_all_refs', 'refworks'), 'type' => 'refworks', 'link' => $refer),array('name'=>get_string('add_to_folder_title', 'refworks'),'type'=>'refworks'));
refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();

refworks_base::write_sidemenu('viewrefs.php');

//Main content goes here

refworks_base::write_heading(get_string('add_to_folder_title', 'refworks'));

refworks_connect::write_login_errors();
?>
<div class="instructions">
<p>Select the folder you wish to add the reference to using the drop-down menu, then select <em>add to folder</em>.
</p>
<p>
You can create a folder by selecting the <em>Create folder</em> link, under the <em>Folders</em> side-menu.
</p></div>
<?php
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}

//check capability
if (!refworks_base::check_capabilities('mod/refworks:folders')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('add_to_folder_title','refworks')));
    refworks_base::write_footer();
    exit;
}

$mform = new refworks_addreferences_form();
//////////////////////////////
if ($mform->is_cancelled()) {
    //you need this section if you have a cancel button on your form
    //here you tell php what to do if your user presses cancel
    //probably a redirect is called for!
    redirect($refer, '', 0);
    refworks_base::write_footer();
} else if ($fromform=$mform->get_data() || $fld!='') {
    //this branch is where you process validated data.

    $result = false;
    if ($fld!='') {
        $result = refworks_folder_api::add_ref_to_folder($rid, $fld);
    }

    if ($result === false) {
        throw new moodle_exception('addtofolder_error', 'refworks', $refer, refworks_base::return_foldername($fld,true));
        //refworks_base::write_error(get_string('addtofolder_error','refworks',refworks_base::return_foldername($fld,true)));
    } else {
        notify(get_string('addtofolder_success','refworks',htmlspecialchars(refworks_base::return_foldername($fld,true))),'notifysuccess');
    }

    redirect($refer, '', 1);

    refworks_base::write_footer();

} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    // Show header
    echo $OUTPUT->box_start('generalbox', 'resourcepage_reference');
    //put data you want to fill out in the form into array $toform here then :
    //$mform->set_data($toform);

    $form=$mform->_formref;
    if (refworks_base::$isinstance) {
        $form->addElement('hidden', 'id', refworks_base::$cm->id);
    }
    $form->addElement('hidden', 'refer', $refer);
    $form->addElement('hidden', 'rid', $rid);

    $mform->display();

    echo $OUTPUT->box_end();
    refworks_base::write_footer();
    if (refworks_base::$isinstance) {
        add_to_log(refworks_base::$course->id,'refworks','view','moveref.php?id='.refworks_base::$cm->id,'Access RefWorks reference add to folder',refworks_base::$cm->id);
    }
}
?>