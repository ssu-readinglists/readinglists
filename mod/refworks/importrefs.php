<?php
/**
 * View Import References references screen
 *
 * @copyright &copy; 2009 The Open University
 * @author j.ackland-snow@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */
require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/refworks_display.php');
require_once(dirname(__FILE__) . '/../../local/references/convert/refxml.php');
GLOBAL $CFG, $OUTPUT;
require_once($CFG->libdir.'/formslib.php');
/**
 * Displays an import form to allow for upload & process of reference file based on j.platt's resourcepage_addreferences_form (resourcepage)
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author j.ackland-snow@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */
class refworks_addreferences_form extends moodleform {

    public $_formref;

    function definition() {

        global $COURSE;
        $mform    =& $this->_form;
        $this->_formref = $this->_form;
        //-------------------------------------------------------------------------------
        // Add General box
        $mform->addElement('header', 'general', get_string('general', 'form'));
        //$this->set_upload_manager(new upload_manager('attachment', true, false, $COURSE, false, 512000 , true, true, false));
        //$mform->addElement('file', 'attachment', get_string('reference_file', 'refworks'));
        $mform->addElement('filepicker', 'attachment', get_string('reference_file', 'refworks'), null, array('maxbytes' => 512000, 'accepted_types' => array('.xml', '.txt', '.ris')));
        $mform->addRule('attachment', null, 'required');

        if (refworks_base::check_capabilities('mod/refworks:folders')) {
            $folderlist = array();
            $folderlist[''] = get_string('none');
            for ($a=0, $max=count(refworks_folder_api::$folders); $a<$max; $a++) {
                $folderlist[refworks_folder_api::$folders[$a]['name']] = htmlspecialchars(refworks_base::return_foldername(refworks_folder_api::$folders[$a]['name'],true));
            }
            $mform->addElement('select', 'fld', get_string('select_folder', 'refworks'), $folderlist);
        }

        //-------------------------------------------------------------------------------
        // Add Save Changes and Cancel buttons
        $this->add_action_buttons(false,get_string('import_ref','refworks'));
    }
}
refworks_base::init();
$curbreadcrumb = array(array('name' => get_string('import_ref', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();

refworks_base::write_sidemenu();

//Main content goes here

refworks_base::write_heading(get_string('import_ref', 'refworks'));

refworks_connect::write_login_errors();
?>
<div class="instructions">
<p>You can upload a file from your computer and transfer the references
into your library.</p>
<p>The following file formats are supported:</p>
<ul>
    <li>RIS (this is a format used by RefWorks and many similar software
    packages. It is also offered as a format for saving records by <strong>some</strong>
    online databases. RIS files will usually have .txt or .ris at the end
    of the file name)</li>
    <li>RefWorks Tagged XML (this is a format used by RefWorks. RefWorks
    XML files will usually have .xml at the end of the file name)</li>
</ul>
<p>It is not possible to import .txt or .xml files into MyReferences that <strong>do not</strong>
contain data in RIS or RefWorks Tagged XML formats.</p>
<p>Select the <em>Choose a file</em> button and navigate to where
the file is located on your computer, then select <em>import references</em>.
Select the folder the imported references will be added to by using the
drop-down menu .</p>
</div>
<?php
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}

$mform = new refworks_addreferences_form();
//////////////////////////////
if ($fromform=$mform->get_data()) {
    //this branch is where you process validated data.

    $uploaded=$mform->get_file_content('attachment');
    $refxml = new refxml();
    if (mb_detect_encoding($uploaded, "UTF-8, ASCII, ISO-8859-1")!="UTF-8") {
        $uploaded = utf8_encode($uploaded);
    }
    $returnedtype = false;

    if (isset($fromform->fld)) {
        $fld = $fromform->fld;
        $fld = refworks_base::return_foldername($fld);
    } else {
        $fld = '';
    }

    try{
        if ($refxml->test_data_type($uploaded,'RIS')) {
			// To avoid existing References IDs being converted to Notes on RefWorks Import
			// clean out any IDs already in the reference file
			// added by OS for Southampton Solent University 09/03/2012
			$uploaded = preg_replace("/ID  - [A-Za-z0-9]{1,20}/","",$uploaded);
            $import = refworks_ref_api::import_refs($uploaded,4,$fld);
        }else if ($refxml->test_data_type($uploaded,'RefWorksXML')) {
			// To avoid existing References IDs being converted to Notes on RefWorks Import
			// clean out any IDs already in the reference file
			// added by OS for Southampton Solent University 09/03/2012
			$uploaded = preg_replace("/<id>[A-Za-z0-9]{1,20}<\/id>/","",$uploaded);
            $import = refworks_ref_api::import_refs($uploaded,156,$fld);
        } else {
            throw new moodle_exception('reference_import_failfile', 'refworks');
        }
        if ($import == true) {
            notify(get_string('reference_import_success','refworks'),'notifysuccess');
        } else {
            throw new moodle_exception('reference_import_fail', 'refworks');
        }
    }catch (moodle_exception $e) {
        refworks_base::write_error($e->getMessage());
    }
}
// this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
// or on the first display of the form.

$title=get_string('import_ref','refworks');
// Show header
//print_header($cm,$course,$refworks,$title,false,'',$mform->focus()); //nedds sorting
//print_heading_with_help($title, "reference", 'refworks');
echo $OUTPUT->box_start('generalbox', 'resourcepage_reference');

//put data you want to fill out in the form into array $toform here then :
//$mform->set_data($toform);
if (refworks_base::$isinstance) {
    $form=$mform->_formref;
    $form->addElement('hidden', 'id', refworks_base::$cm->id);
}
$mform->display();
//print_footer($course);
echo $OUTPUT->box_end();
refworks_base::write_footer();
//add_to_log($course->id,'resourcepage','view','','Import references form',$id);


if (refworks_base::$isinstance) {
    add_to_log(refworks_base::$course->id,'refworks','view','importrefs.php?id='.refworks_base::$cm->id,'Access RefWorks reference import',refworks_base::$cm->id);
}

?>