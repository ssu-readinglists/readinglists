<?php
/**
 * Manage references. Has 3 main states:
 * 0. Access (download) attachment (attachment param)
 * 1. Delete ref
 * 1.1 - Confirm delete (name delete submit)
 * 1.2 - Action delete (delete 1) or cancel (delete 2)
 * 2. Update ref fields (mform)
 * 2.1 Edit boxes (name update submit)
 * 2.2 form submit/cancel
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/search_base.php');
require_once(dirname(__FILE__).'/../../local/references/getdata.php');
global $CFG, $OUTPUT;
require_once($CFG->libdir.'/filelib.php');

refworks_base::init();

$rid = required_param('refid', PARAM_INT);

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad');
}

//work out where we cam from
$refer = optional_param('refer', '' , PARAM_URL);
if ($refer == '') {
    if (isset($_SERVER['HTTP_REFERER'])) {
        if (strpos($_SERVER['HTTP_REFERER'], 'viewrefs.php')!==false || strpos($_SERVER['HTTP_REFERER'], 'viewfolder.php')!==false || strpos($_SERVER['HTTP_REFERER'], 'reports')!==false) {
            $refer = $_SERVER['HTTP_REFERER'];
        }
    }
}
if ($refer == '') {
    $refer = refworks_base::return_link('viewrefs.php');
}

//check for state 0 - download an specified ref attachment
$attachment = optional_param('attachment','',PARAM_FILE);
if ($attachment!='') {
    refworks_connect::require_login();
    if (refworks_connect::$connectok) {
        //get attachment from rw api and download to http stream
        $result = refworks_ref_api::get_attachment($rid,urldecode($attachment));
        if (!$result) {
            refworks_base::write_header();
            refworks_base::write_sidemenu();
            refworks_base::write_error(get_string('attachment_error','refworks',refworks_ref_api::$lasterror));
            print_continue($refer);
            refworks_base::write_footer();
        } else {
            send_file($result,$attachment,'default',0,true,true);
        }
        exit;
    }
}
//check for state 1 (delete img button)
$delete = optional_param('delete_x','-1',PARAM_INT);
$dodelete = optional_param('delete',0,PARAM_INT);

if ($dodelete==1) {
    //cancel delete
    redirect($refer,'',0);
}

if ($delete != -1 || $dodelete>0) {
    $curbreadcrumb = array(array('name' => get_string('delete_ref', 'refworks'), 'type' => 'refworks'));
    $heading = get_string('delete_ref', 'refworks');
} else {
    $curbreadcrumb = array(array('name' => get_string('update_ref', 'refworks'), 'type' => 'refworks'));
    $heading = get_string('update_ref', 'refworks');
}
refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();

refworks_base::write_sidemenu();

//Main content goes here

refworks_base::write_heading($heading);

refworks_connect::write_login_errors();

if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}

if ($delete != -1 || $dodelete>0) {
    //In delete (not update)
    //check capability
    if (!refworks_base::check_capabilities('mod/refworks:delete')) {
        refworks_base::write_error(get_string('nopermissions','error',get_string('refworks:delete','refworks')));
        refworks_base::write_footer();
        exit;
    }

    if ($delete!=-1) {
        //write delete confirm
        //check user is OK with removing ref
        //setup hidden fields for yes/no forms
        $yesfields = array('delete'=>2, 'refid'=>$rid, 'sesskey'=>sesskey(), 'refer'=>$refer);
        if (refworks_base::$isinstance) {
            $yesfields['id'] = refworks_base::$cm->id;
        }

        $nofields = array('delete'=>1, 'refid'=>$rid, 'sesskey'=>sesskey(), 'cancelurl'=>$refer, 'refer'=>$refer);
        if (refworks_base::$isinstance) {
            $nofields['id'] = refworks_base::$cm->id;
        }

        echo $OUTPUT->confirm(get_string('sure_delete_ref','refworks'),
        new moodle_url('managerefs.php', $yesfields),
        new moodle_url('managerefs.php', $nofields));

    } else if ($dodelete==2) {
        //delete the ref
        $result = false;

        $result = refworks_ref_api::delete_ref($rid);

        if ($result === false) {
            throw new moodle_exception('deleteref_error', 'refworks', $refer);
            //refworks_base::write_error(get_string('deleteref_error','refworks'));
        } else {
            notify(get_string('deleteref_success','refworks'),'notifysuccess');
        }

        redirect($refer);
    }

    refworks_base::write_footer();
    exit;//exit so don't do any update form stuff
}

//function used to get ref xml from refworks api (used for form filling + saving)
//will write error + exit if there is an error
function getcurref($rid) {
    //get xml of ref
    $result = refworks_ref_api::get_ref_data('&id='.$rid);
    if (!$result) {
        refworks_base::write_error(get_string('getref_error','refworks'));
        refworks_base::write_footer();
        exit;
    }
    return $result;
}

// Create Reference form - do this first so it gets id of mform1 otherwise javascript in refworks.js won't work :(
require_once(dirname(__FILE__).'/refworks_managerefs_form.php');
//set flag to create the holding div for the display fields control link to true
refworks_managerefs_form::$showtoggle = true;
$mform = new refworks_managerefs_form();

// Create Search Forms
require_once(dirname(__FILE__).'/search_form.php');
$doi_s_form = new search_doi_form();
$isbn_s_form = new search_isbn_form();
$issn_s_form = new search_issn_form();
$primorid_s_form = new search_primorid_form();

$form=$mform->_formref;
//create hidden elements for ibsn and doi retrieval handling
$form->addElement('hidden', 'hiddensn', '');
$form->addElement('hidden', 'hiddendoi', '');


if (refworks_base::check_capabilities('mod/refworks:upload_attachments')) {
    global $COURSE;
    //$mform->set_upload_manager(new upload_manager('attachment', true, false, $COURSE, false, 20971520 , true, true, false));
    //$form->addElement('file', 'attachment', get_string('attach_file', 'refworks'));
    $form->addElement('filepicker', 'newattachment', get_string('attach_file', 'refworks'), null, array('maxbytes' => 20971520, 'accepted_types' => '*'));
}

//check if we have any search values submitted
//would be nice if Search defined these?
$search_doi = optional_param('s_doi','',PARAM_TEXT);
$search_isbn = optional_param('s_isbn','',PARAM_TEXT);
$search_issn = optional_param('s_issn','',PARAM_TEXT);
$search_primorid = optional_param('s_primorid','',PARAM_TEXT);


//get DOI/ISBN retrive variables
$getdata = optional_param('get_data','none',PARAM_TEXT);
$getdataisbn = optional_param('get_data_isbn','none',PARAM_TEXT);
$doivalue = optional_param('do','',PARAM_TEXT);
$snvalue = optional_param('sn','',PARAM_TEXT);
$hiddensnvalue = optional_param('hiddensn','',PARAM_TEXT);
$hiddendoivalue = optional_param('hiddendoi','',PARAM_TEXT);
$rtvalue = optional_param('rt','',PARAM_TEXT);

if (optional_param('cancel', 0, PARAM_RAW)) {
    //you need this section if you have a cancel button on your form
    //here you tell php what to do if your user presses cancel
    //probably a redirect is called for!
    redirect($refer, '', 0);
    refworks_base::write_footer();
    exit;
} elseif ($search_doi || $search_isbn || $search_issn || $search_primorid) { // 'Get data' button has been pressed
    error_log("Got a search");
    if ($search_doi) {
        error_log("Doing DOI search");
        if ($hiddendoivalue!=='') {
                $search_doi = $hiddendoivalue; //contingency for browsers with javascript disabled
            }
        if ($search_doi!=='') {
            $getreffromdoi = references_getdata::call_crossref_api($search_doi);
            if ($getreffromdoi) {
                foreach (refworks_managerefs_form::$reffields as $field) {
                    // Keep Reference type
                    if($field!='rt'){
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
    } elseif ($search_isbn) {
        error_log("Doing ISBN search");
       if ($search_isbn!=='') {
           //$search_isbn = $hiddensnvalue; //contingency for browsers with javascript disabled
       }
       if ($search_isbn!=='') {
            $getrefattempts = '';
            $getreffromisbn = false;
            // Try using Primo first (http://www.exlibrisgroup.com/category/PrimoOverview)
            // Added 28/03/2012 owen@ostephens.com
            $getreffromisbn = references_getdata::call_primo_api($search_isbn,'isbn',$rtvalue);
            error_log("Get ref from ISBN ".$getreffromisbn);
            $getrefattempts .= get_string('isbn_primo','refworks').' ';
            // If Primo fails, try WorldCat
            if ($getreffromisbn === false) {
                $getreffromisbn = references_getdata::call_worldcat_api($search_isbn);
                $getrefattempts .= get_string('isbn_worldcat','refworks');
            }
            if ($getreffromisbn) {
                foreach (refworks_managerefs_form::$reffields as $field) {
                // Keep reference type
                    if ($field!='rt') {
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
    } elseif ($search_issn) {
        error_log("Doing ISSN search");
        if ($search_issn!=='') {
            $getrefattempts = '';
            $getreffromissn = false;
            $getreffromissn = references_getdata::call_primo_api($search_issn,'issn',$rtvalue);
            error_log("Get ref from ISSN ".$getreffromissn);
            $getrefattempts .= get_string('issn_primo','refworks').' ';
            // If Primo fails, no other attempts currently
            if ($getreffromissn) {
                foreach (refworks_managerefs_form::$reffields as $field) {
                    // Keep Reference Type
                    if ($field!='rt') {
                        if (isset(references_getdata::$retrievedarray[$field])) {
                            $form->setConstant($field,references_getdata::$retrievedarray[$field]);
                        } else {
                            $form->setConstant($field,'');
                        }
                    }
                    // Set Hidden DOI - probably not needed - for review
                    if ($field=='do') {
                        if (isset(references_getdata::$retrievedarray[$field])) {
                            $form->setConstant('hiddendoi',references_getdata::$retrievedarray[$field]);
                        } else {
                            $form->setConstant('hiddendoi','');
                        }
                    }
                }
            } else if ($getreffromissn === false) {
                refworks_base::write_error(get_string('issn_getref_empty','refworks').' '.$getrefattempts);
            }
        }
    } elseif ($search_primorid) {
        error_log("Doing Primo Record ID search");
        if ($search_primorid!=='') {
            $getreffromprimorid = false;
            $getreffromprimorid = references_getdata::call_primo_api($search_primorid,'recordid',$rtvalue);
            error_log("Get ref from Primo Record ID ".$getreffromprimorid);
            if ($getreffromprimorid) {
                foreach (refworks_managerefs_form::$reffields as $field) {
                    // Keep Reference Type
                    if ($field!='rt') {
                        if (isset(references_getdata::$retrievedarray[$field])) {
                            $form->setConstant($field,references_getdata::$retrievedarray[$field]);
                        } else {
                            $form->setConstant($field,'');
                        }
                    }
                }
            } elseif ($getreffromprimorid == false) {
                refworks_base::write_error(get_string('primorid_getref_empty','refworks'));
            }
        }
    }
}else if ($fromform=$mform->get_data()) {
    //this branch is where you process validated data.

    //build up xml string of reference changes
    $xmlstring = '<reference>';

    $xmlstring .= '<id>'.$rid.'</id>';

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
                //split author string by ; (make sure any html special chars are taken out first as they have ; !)
                $auths = html_entity_decode($fromform->$curfield, ENT_QUOTES);
                $auarray = explode(';', $auths);
                if ($auarray[count($auarray)-1] == '' || $auarray[count($auarray)-1] == ' ') {
                    array_pop($auarray);
                }
                //If nothing enetered create empty tag (to override any existing vals)
                if (count($auarray)==0) {
                    $auarray[] = '';
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
            default:
                $newval = $fromform->$curfield;
                $xmlstring .= '<'.$curfield.'>'.$newval.'</'.$curfield.'>';
                break;
        }
    }
    $xmlstring .= '</reference>';

    //do refworks api call with new ref xml
    $result = refworks_ref_api::update_ref($xmlstring);

    if ($result === false) {
        refworks_base::write_error(get_string('refsave_error','refworks'));
    } else {
        notify(get_string('refsave_success','refworks'),'notifysuccess');
        //add attachment
        $attachcontents=$mform->get_file_content('newattachment');
        if ($attachcontents!='') {
            //$attachname = $mform->_upload_manager->files['attachment']['name'];
            $attachname = $mform->get_new_filename('newattachment');
            $result = refworks_ref_api::add_attachment($rid,$attachcontents,$attachname);
            if ($result == false) {
                refworks_base::write_error(get_string('attachment_uploaderror','refworks',$attachname));
            }
        }
        //delete any attachments (these are in checkboxes named "at_")
        global $_POST;
        foreach ($_POST as $postname=>$postval) {
            if (strpos($postname,'at_')===0 && $postval==1) {
                $attachname = htmlspecialchars_decode(substr($postname,3));
                $attachname = str_replace('*?','.',$attachname);
                $result = refworks_ref_api::delete_attachment($rid,$attachname);
                if ($result == false) {
                    refworks_base::write_error(get_string('attachment_deleteerror','refworks',$attachname));
                }
            }
        }
        if (refworks_base::$isinstance) {
            add_to_log(refworks_base::$course->id,'refworks','manageref','managerefs.php?id='.refworks_base::$cm->id,'Made reference update.',refworks_base::$cm->id);
        }
    }
    //now display form as usual...
}

// this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
// or on the first display of the form.

$result = getcurref($rid);
//work out existing values for fields
$existvals = array();
//get reference from result
$resxml=new domDocument();
$resxml->loadXML($result);
$refnode = $resxml->getElementsByTagName('reference')->item(0);

for ($a=0,$max=count(refworks_managerefs_form::$reffields);$a<$max;$a++) {
    $curfield = refworks_managerefs_form::$reffields[$a];
    $fieldnodes = $refnode->getElementsByTagName($curfield);
    if ($fieldnodes->length>=1) {
        switch($curfield) {
            case 'a1':
                //special case for authors as they should be split/joined from field to multiple a1 elements
                $nodeval = '';
                for ($b=0,$max2=$fieldnodes->length;$b<$max2;$b++) {
                    $nodeval .= $fieldnodes->item($b)->nodeValue.'; ';
                }
                $existvals['a1'] = $nodeval;
                break;
            case 'rt':
                //special case for type as string needs to be turned to number for drop-down
                $nodeval = $fieldnodes->item(0)->nodeValue;
                if (in_array($nodeval, refworks_managerefs_form::$reftypes)) {
                    $existvals['rt'] = array_search($nodeval, refworks_managerefs_form::$reftypes);
                } else {
                    //can't find a matching type - use generic
                    $existvals['rt'] = array_search('Generic', refworks_managerefs_form::$reftypes);
                }
                break;

            default:
                $nodeval = $fieldnodes->item(0)->nodeValue;
                if ($nodeval != '') {
                    $existvals[$curfield] = htmlspecialchars_decode($nodeval, ENT_QUOTES);
                }
                break;
        }
    }
}
global $OUTPUT;
//create attachment remove options
if (refworks_base::check_capabilities('mod/refworks:upload_attachments')) {
    $attaches = $resxml->getElementsByTagName('at');
    foreach ($attaches as $attach) {
        $attachname = htmlspecialchars($attach->nodeValue);
        //get icon
        $pix = $OUTPUT->pix_url('/f/'.mimeinfo('icon',$attachname));
        //replace . as this gets turned to _ (with some illegal chars so should not cause prob when switching back)
        $form->addElement('checkbox','at_'.str_replace('.','*?',$attachname),get_string('attach_file_remove','refworks'),'<img src="'.$pix.'" alt="" />'.$attachname);
    }
}
// Show header

echo $OUTPUT->box_start('generalbox', 'resourcepage_reference');
//put data you want to fill out in the form into array $toform here then :
$mform->set_data($existvals);

// Add reference elements to Search forms
$doi_s_form->_formref->addElement('hidden','refid', $rid);
$isbn_s_form->_formref->addElement('hidden','refid', $rid);
$issn_s_form->_formref->addElement('hidden','refid', $rid);
$primorid_s_form->_formref->addElement('hidden','refid', $rid);

// Add reference elements to Reference details form
if (refworks_base::$isinstance) {
    $form->addElement('hidden', 'id', refworks_base::$cm->id);
}
$form->addElement('hidden', 'refer', $refer);
$form->addElement('hidden', 'refid', $rid);

$mform->add_action_buttons(true,get_string('update_ref','refworks'));

//Display search forms
echo $OUTPUT->box_start('generalbox', 'resourcepage_reference');

echo '<div id="searchfields">';
echo '<div id="searchdoi">';
$doi_s_form->display();
echo '</div><div id="searchisbn">';
$isbn_s_form->display();
echo '</div><div id="searchissn">';
$issn_s_form->display();
echo '</div><div id="searchprimorid">';
$primorid_s_form->display();
echo '</div>';
echo $OUTPUT->box_end();

//Display reference details form
echo '<div id="referencedetail">';
$mform->display();
echo '</div>';

echo $OUTPUT->box_end();
refworks_base::write_footer();
if (refworks_base::$isinstance) {
    add_to_log(refworks_base::$course->id,'refworks','view','managerefs.php?id='.refworks_base::$cm->id,'Access reference update.',refworks_base::$cm->id);
}

?>