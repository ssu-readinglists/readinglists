<?php
/**
 * Front screen of module (duplicate of index.php)
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author j.ackland-snow@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */
require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/refworks_display.php');
require_once($CFG->libdir.'/formslib.php');
refworks_base::init();
$fld = 'unsubmitted';
$fld = optional_param('foldername', 'unsubmitted', PARAM_TEXT);
$fld = refworks_base::return_foldername($fld);
$namey = optional_param('namey', 'unsubmitted', PARAM_TEXT);
$oldname = optional_param('fldold', 'unsubmitted', PARAM_TEXT);
//write header
$curbreadcrumb = array(array('name' => get_string('manage_folder', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

class refworks_renamefolder_form extends moodleform {
    public $_formref;

    function definition() {

        global $COURSE;
        $nform    =& $this->_form;
        $this->_formref = $this->_form;
        //-------------------------------------------------------------------------------
        // Add General box
        /// Adding the standard "name" field
        $nform->addElement('text', 'namey', get_string('new_folder_name', 'refworks'), array('size'=>'64'));
        $nform->setType('namey', PARAM_TEXT);
        $nform->addRule('namey', null, 'required');
        $nform->addRule('namey', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        //-------------------------------------------------------------------------------
        // Add Save Changes and Cancel buttons
        $this->add_action_buttons(true,get_string('folder_rename','refworks'));
    }
}

function common_prelimininaries() {
    //check session key if form actioned
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }
    if (!refworks_connect::$connectok) {
        print_error('connection_error','refworks',refworks_base::return_link('managefolders.php'));
    }

}
function common_postlimininaries($instruct=true) {
    refworks_base::write_sidemenu();
    refworks_base::write_heading(get_string('manage_folder', 'refworks'));
    if (!refworks_base::check_capabilities('mod/refworks:folders')) {
        refworks_base::write_error(get_string('nopermissions','error',get_string('manage_folder','refworks')));
        refworks_base::write_footer();
    exit;
    }
    refworks_connect::write_login_errors();
    if (!refworks_connect::$connectok) {
        //No RefWorks session? Do nothing instead.
        refworks_base::write_footer();
    exit;
    }
    if ($instruct) {
        global $CFG;
        $del = refworks_display::get_image_paths()->delete;
        $delfolder = refworks_display::get_image_paths()->delfolder;
        $edit = refworks_display::get_image_paths()->edit;
        $instructions=<<<INST
        <div class="instructions"><p>
On this screen you can manage any folders in your library.
</p>
<table width="100%" border="1" summary="List of buttons that give extra functionality for each reference">
  <tr>
    <th width="30" scope="row"><img src="$del" alt="delete folder icon" /></th>
    <td>Delete folder: Deletes the selected folder and any references contained within it.</td>
  </tr>
  <tr>
    <th scope="row"><img src="$delfolder" alt="remove folder icon" /></th>
    <td>Remove folder: Removes the folder, does not delete any references that are in it.</td>
  </tr>
  <tr>
    <th scope="row"><img src="$edit" alt="rename folder icon" /></th>
    <td>Rename folder: Change the name of the folder.</td>
  </tr>
</table>
</div>
INST;
        echo($instructions);
    }
}

refworks_connect::require_login();


$refer = refworks_base::return_link('managefolders.php');

// if form action
$delete = optional_param('delete_x','-1',PARAM_INT);
$deletefolder = optional_param('delete_folder_x','-1',PARAM_INT);
$dodelete = optional_param('delete',0,PARAM_INT);
$dodeletefolder = optional_param('delete_folder',0,PARAM_INT);
$rename = optional_param('update_x','-1',PARAM_INT);

//beginning of switch
//set value of pageselector
$pageselector = 'none'; //default to opening state view at bottom of page after switch if case not found
if ($delete!=-1) {
        $pageselector = 'deletesure';
}elseif ($deletefolder!=-1) {
        $pageselector = 'deletefoldersure';
}elseif ($rename!=-1 || $namey!='unsubmitted') {
        $pageselector = 'rename';
}
if ($dodelete==2) {
    $pageselector = 'deletemessage';
}elseif ($dodeletefolder==2) {
        $pageselector = 'deletefoldermessage';
}
switch($pageselector) {
    case 'deletesure':
        common_prelimininaries();
        common_postlimininaries(false);
        //setup hidden fields for yes/no forms
        $yesfields = array('delete'=>2, 'sesskey'=>sesskey(), 'refer'=>$refer, 'foldername'=>$fld);
        if (refworks_base::$isinstance) {
            $yesfields['id'] = refworks_base::$cm->id;
        }
        $nofields = array('delete'=>1, 'sesskey'=>sesskey(), 'cancelurl'=>$refer, 'refer'=>$refer);
        if (refworks_base::$isinstance) {
            $nofields['id'] = refworks_base::$cm->id;
        }
        global $OUTPUT;
        echo $OUTPUT->confirm(get_string('sure_remove_folder_and_refs','refworks'),
            new moodle_url('', $yesfields), new moodle_url('', $nofields));

        refworks_base::write_footer();
        exit;
        break;
    case 'deletemessage':
        $pieces = refworks_ref_api::merge_id_list('','pad'.$fld);
        $result = refworks_folder_api::delete_folder_refs($pieces);
        refworks_folder_api::delete_folder_only($fld);
        common_prelimininaries();
        common_postlimininaries();
        refworks_display::write_folder_list(refworks_folder_api::$folders,  refworks_display::managefoldersaction);
        refworks_base::write_footer();
        exit;
        break;
    case 'deletefoldersure':
        common_prelimininaries();
        common_postlimininaries(false);
        //setup hidden fields for yes/no forms
        $yesfields = array('delete_folder'=>2, 'sesskey'=>sesskey(), 'refer'=>$refer, 'foldername'=>$fld);
        if (refworks_base::$isinstance) {
            $yesfields['id'] = refworks_base::$cm->id;
        }
        $nofields = array('delete_folder'=>1, 'sesskey'=>sesskey(), 'cancelurl'=>$refer, 'refer'=>$refer);
        if (refworks_base::$isinstance) {
            $nofields['id'] = refworks_base::$cm->id;
        }
        global $OUTPUT;
        echo $OUTPUT->confirm(get_string('sure_remove_folder_only','refworks'),
            new moodle_url('', $yesfields), new moodle_url('', $nofields));

        refworks_base::write_footer();
        exit;
        break;
    case 'deletefoldermessage':
        refworks_folder_api::delete_folder_only($fld);
        common_prelimininaries();
        common_postlimininaries();
        refworks_display::write_folder_list(refworks_folder_api::$folders,  refworks_display::managefoldersaction);
        refworks_base::write_footer();
        exit;
        break;
    case 'rename':
        common_prelimininaries();
        $nform = new refworks_renamefolder_form();
        if (optional_param('cancel', 0, PARAM_RAW)) {
            //you need this section if you have a cancel button on your form
            //here you tell php what to do if your user presses cancel
            //probably a redirect is called for!
            continue;
        }else if ($fromform=$nform->get_data()) { //data received from the rename form
            refworks_folder_api::rename_folder(refworks_base::return_foldername($oldname),stripslashes($namey));
            continue;
        } else { //initial view of the rename form
            $form=$nform->_formref;
            $form->addElement('hidden', 'fldold', $fld);
            $form->addElement('hidden', 'refer', $refer);
            if (refworks_base::$isinstance) {
                $form->addElement('hidden', 'id', refworks_base::$cm->id);
            }
            common_postlimininaries();
            $nform->set_data(array('namey'=>refworks_base::return_foldername($fld,true)));
            $nform->display();
            refworks_base::write_footer();
            exit;
        }
        break;
}
//end of switch
common_postlimininaries();
?>

<?php
if (count(refworks_folder_api::$folders)>0) {
    refworks_display::write_folder_list(refworks_folder_api::$folders,  refworks_display::managefoldersaction);
}
refworks_base::write_footer();
?>