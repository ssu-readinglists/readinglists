<?php
/**
 * Create references. Has 2 main states:
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/../../local/references/getdata.php');
require_once(dirname(__FILE__).'/refworks_display.php');
refworks_base::init();

$curbreadcrumb = array(array('name' => get_string('create_ref', 'refworks'), 'type' => 'refworks'));
$heading = get_string('create_ref', 'refworks');

refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();

refworks_base::write_sidemenu();

//Main content goes here

    //check capability
if (!refworks_base::check_capabilities('mod/refworks:update')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('create_ref','refworks')));
    refworks_base::write_footer();
    exit;
}

refworks_base::write_heading($heading);
refworks_connect::write_login_errors();
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}
//get the form for creating the reference
require_once(dirname(__FILE__).'/refworks_managerefs_form.php');

unset(refworks_managerefs_form::$reftypes[array_search('Dissertation/Thesis Unpublished',refworks_managerefs_form::$reftypes)]);

//set flag to create the holding div for the display fields control link to true
refworks_managerefs_form::$showtoggle = true;
//create new instance of the form
$mform = new refworks_managerefs_form();
$form=&$mform->_formref;

//create hidden elements for ibsn and doi retrieval handling
$form->addElement('hidden', 'hiddensn', '');
$form->addElement('hidden', 'hiddendoi', '');

if (refworks_base::check_capabilities('mod/refworks:upload_attachments')) {
    global $COURSE;
    //$mform->set_upload_manager(new upload_manager('attachment', true, false, $COURSE, false, 20971520 , true, true, false));
    //$form->addElement('file', 'attachment', get_string('attach_file', 'refworks'));
    $form->addElement('filepicker', 'attachment', get_string('attach_file', 'refworks'), null, array('maxbytes' => 20971520, 'accepted_types' => '*'));
}

//check if get data button has been pressed
$getdata = optional_param('get_data','none',PARAM_TEXT);
$getdataisbn = optional_param('get_data_isbn','none',PARAM_TEXT);
$doivalue = optional_param('do','',PARAM_TEXT);
$snvalue = optional_param('sn','',PARAM_TEXT);
$hiddensnvalue = optional_param('hiddensn','',PARAM_TEXT);
$hiddendoivalue = optional_param('hiddendoi','',PARAM_TEXT);
$rtvalue = optional_param('rt','',PARAM_TEXT);
 //test to see which no_submit button been pressed if one has been pressed?
$nosubmitpressed = '';
foreach ($form->_noSubmitButtons as $nosubmitbutton) {
     if (optional_param($nosubmitbutton, 0, PARAM_RAW)) {
            $nosubmitpressed = $nosubmitbutton;
            break;
     }
}

if ($mform->no_submit_button_pressed()) { // 'Get data' button has been pressed
    switch($nosubmitpressed) {
        case 'get_data_isbn': //population using isbn
           if ($hiddensnvalue!=='') {
               $snvalue = $hiddensnvalue; //contingency for browsers with javascript disabled
           }
           if ($snvalue!=='') {
				$getrefattempts = '';
				$getreffromisbn = false;
				// Try using Primo first (http://www.exlibrisgroup.com/category/PrimoOverview)
				// Added 28/03/2012 owen@ostephens.com
				$getreffromisbn = references_getdata::call_primo_api($snvalue,'isbn',$rtvalue);
				$getrefattempts .= get_string('isbn_primo','refworks').' ';
				// If Primo fails, try WorldCat
				if ($getreffromisbn === false) {
					$getreffromisbn = references_getdata::call_worldcat_api($snvalue);
					$getrefattempts .= get_string('isbn_worldcat','refworks');
				}
				if ($getreffromisbn) {
					foreach (refworks_managerefs_form::$reffields as $field) {
					//keep the ISBN entered
						if ($field!='sn' && $field!='do' && $field!='rt') {
							if (isset(references_getdata::$retrievedarray[$field])) {
								$form->setConstant($field,references_getdata::$retrievedarray[$field]);
							} else {
								$form->setConstant($field,'');
							}
						} else if ($field=='do') {
							if (isset(references_getdata::$retrievedarray[$field])) {
								$form->setConstant('hiddendoi',references_getdata::$retrievedarray[$field]);
							} else {
								$form->setConstant('hiddendoi','');
							}
						}
					}
				} else if ($getreffromisbn === false) {
					refworks_base::write_error(get_string('isbn_getref_empty','refworks').' '.$getrefattempts);
				}
			}
			break;
			case 'get_data': //population using doi
			if ($hiddendoivalue!=='') {
				$doivalue = $hiddendoivalue; //contingency for browsers with javascript disabled
			}
			if ($doivalue!=='') {
				$getreffromdoi = references_getdata::call_crossref_api($doivalue);
				if ($getreffromdoi) {
					foreach (refworks_managerefs_form::$reffields as $field) {
						//keep the DOI entered
						// Amended to keep Reference Type entered for SSU. owen@ostephens.com 28th March 2012
						if($field!='do' && $field!='sn' && $field!='rt'){
							if (isset(references_getdata::$retrievedarray[$field])) {
								$form->setConstant($field,references_getdata::$retrievedarray[$field]);
							} else {
								$form->setConstant($field,'');
							}
						} else if ($field=='sn') {
                            if (isset(references_getdata::$retrievedarray[$field])) {
                                $form->setConstant('hiddensn',references_getdata::$retrievedarray[$field]);
                            } else {
                                $form->setConstant('hiddensn','');
                            }
                        }
                    }
                } else if ($getreffromdoi === false) {
                    if (references_getdata::$errorflag == 'unrecognised type') {
                         refworks_base::write_error(get_string('doi_getref_empty','refworks'));
                    } else {
                        refworks_base::write_error(get_string('doi_getref_error','refworks'));
                    }
                }
            }
        break;
        default://TODO
        break;
    }
} elseif ($fromform=$mform->get_data()) {//this branch is where you process validated data.
    //build up xml string of reference changes
    $xmlstring = '<reference>';
    $xmlstring .= '<id>0</id>';
    //prepare inputs by stripping slashes (moodle adds) + making special chars "safe" for xml
    foreach ($fromform as &$propval) {
        $propval = stripslashes($propval);
        $propval = htmlspecialchars($propval, ENT_QUOTES , 'utf-8', false);
    }

    //work through all the fields in the form and update xml vals with submitted values
    for ($a=0,$max=count(refworks_managerefs_form::$reffields);$a<$max;$a++) {
        $curfield = refworks_managerefs_form::$reffields[$a];
        switch($curfield) {
            case 'a1':
                //special case for authors as they should be split/joined from field to multiple a1 elements
                //split author string by ;
                $auths = html_entity_decode($fromform->$curfield, ENT_QUOTES);
                $auarray = explode(';', $auths);
                if ($auarray[count($auarray)-1] == '' || $auarray[count($auarray)-1] == ' ') {
                    array_pop($auarray);
                }
                //now populate a1 nodes with new vals (or, create if not same number as num authors)
                for ($b=0,$max2=count($auarray);$b<$max2;$b++) {
                    $xmlstring .= '<a1>'.htmlspecialchars($auarray[$b], ENT_QUOTES).'</a1>';
                }
                break;
            case 'rt':
                //special case for type as string needs to be turned to number for drop-down
                $newval = refworks_managerefs_form::$reftypes[$fromform->$curfield];
                $xmlstring .= '<rt>'.$newval.'</rt>';
                break;
           case 'sn': //uses hiddden field due to problem clearing moodleform group field
                $newval = $fromform->$curfield;
                if ($hiddensnvalue!='') {
                    $xmlstring .= '<sn>'.$hiddensnvalue.'</sn>';
                } else {
                    $xmlstring .= '<sn>'.$newval.'</sn>';
                }
                break;
           case 'do': //uses hiddden field due to problem clearing moodleform group field
                $newval = $fromform->$curfield;
                if ($hiddendoivalue!='') {
                    $xmlstring .= '<do>'.$hiddendoivalue.'</do>';
                } else {
                    $xmlstring .= '<do>'.$newval.'</do>';
                }
                break;
            default:
                $newval = $fromform->$curfield;
                $xmlstring .= '<'.$curfield.'>'.$newval.'</'.$curfield.'>';
                break;
        }
    }
    $xmlstring .= '</reference>';
    //check if a folder has been selected
    $foldername = optional_param('fld', '', PARAM_TEXT);

    //do refworks api call with new ref xml
    $result = refworks_ref_api::create_ref($xmlstring,refworks_base::return_foldername($foldername));

    if ($result === false) {
        refworks_base::write_error(get_string('refcreate_error','refworks'));
    } else {
        notify(get_string('refcreate_success','refworks'),'notifysuccess');
        //add attachment
        $attachcontents=$mform->get_file_content('attachment');
        $starttagpos = strpos($result,'<id>');
        if ($attachcontents!='' && $starttagpos!==false) {

            //$attachname = $mform->_upload_manager->files['attachment']['name'];
            $attachname = $mform->get_new_filename('attachment');

            $refid=substr($result,$starttagpos+4,strpos($result,'</id>')-$starttagpos-4);
            $result = refworks_ref_api::add_attachment($refid,$attachcontents,$attachname);
            if ($result == false) {
                refworks_base::write_error(get_string('attachment_uploaderror','refworks',$attachname));
            }

        }
        if (refworks_base::$isinstance) {
            add_to_log(refworks_base::$course->id,'refworks','createref','createrefs.php?id='.refworks_base::$cm->id,'Create reference.',refworks_base::$cm->id);
        }
    }
    //now display form as usual...
}

// Show header
//print_simple_box_start('center', '', '', 5, 'generalbox', 'resourcepage_reference');
global $OUTPUT;
echo $OUTPUT->box_start('generalbox', 'resourcepage_reference');
//put data you want to fill out in the form into array $toform here then :

if (refworks_base::$isinstance) {
    $form->addElement('hidden', 'id', refworks_base::$cm->id);
}

if (refworks_base::check_capabilities('mod/refworks:folders')) {
    $folderlist = array();
    $folderlist[''] = get_string('none');
    for ($a=0, $max=count(refworks_folder_api::$folders); $a<$max; $a++) {
        $folderlist[refworks_folder_api::$folders[$a]['name']] = htmlspecialchars(refworks_base::return_foldername(refworks_folder_api::$folders[$a]['name'],true));
    }
    $form->addElement('select', 'fld', get_string('add_to_folder', 'refworks'), $folderlist);
}

$mform->add_action_buttons(false,get_string('create_ref','refworks'));

if ($getdata=='none' && $getdataisbn=='none') { //ie retrieval of reference using DOI or ISBN not instigated
    //always set the fields in the form to empty (makes sure form is empty on submit)
    foreach (refworks_managerefs_form::$reffields as $field) {
        //keep the previous type
        if ($field!='rt'&& $field!='do' && $field!='sn') {
            $form->setConstant($field,'');
        }
    }
}

global $CFG;
echo '<div class="instructions"><p>You do not need to fill in every field, but it is recommended that you fill in as many as possible.</p>';

echo '<p>Once you have saved a reference you can alter the information you entered by selecting the update button <img src="'.refworks_display::get_image_paths()->edit.'" alt="update icon" /> next to the reference in the <em>view all references</em> screen.</p></div>';

//Set Generic as the default type
$form->setDefault('rt',array_search('Book, Whole', refworks_managerefs_form::$reftypes));

echo '<div id="referencedetail">';

$mform->display();
echo '</div>';

//print_simple_box_end();
echo $OUTPUT->box_end();
refworks_base::write_footer();
if (refworks_base::$isinstance) {
    add_to_log(refworks_base::$course->id,'refworks','view','createrefs.php?id='.refworks_base::$cm->id,'Create reference.',refworks_base::$cm->id);
}
?>