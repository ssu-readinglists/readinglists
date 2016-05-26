<?php
/**
 * Removes a ref (rid) from folder (folder)
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */
require_once(dirname(__FILE__).'/refworks_base.php');

refworks_base::init();
//param sent by user confirmation
$userok = optional_param('userok',0,PARAM_INT);
//work out where we cam from
$refer = optional_param('refer', '' , PARAM_URL);
if ($refer == '') {
    if (isset($_SERVER['HTTP_REFERER'])) {
        if (strpos($_SERVER['HTTP_REFERER'], 'viewfolder.php')!==false) {
            $refer = $_SERVER['HTTP_REFERER'];
        }
    }
}
if ($refer == '') {
    $refer = refworks_base::return_link('viewfolder.php?folder='.urlencode($fld));
}
//If user cancelled - show continue
if ($userok == 1) {
    redirect($refer,'',0);
    refworks_base::write_footer();
    exit;
}
$rid = required_param('rid', PARAM_INT);
$fld = required_param('folder',PARAM_TEXT);
$fld = refworks_base::return_foldername($fld);

$curbreadcrumb = array(array('name' => get_string('viewfolder', 'refworks', refworks_base::return_foldername($fld,true)), 'type' => 'refworks', 'link' => $refer),array('name'=>get_string('remove_from_folder_title', 'refworks'),'type'=>'refworks'));
refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();

refworks_base::write_sidemenu('viewfolder.php?folder='.urlencode($fld));

//Main content goes here

refworks_base::write_heading(get_string('remove_from_folder_title', 'refworks'));

refworks_connect::write_login_errors();

if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}

//check capability
if (!refworks_base::check_capabilities('mod/refworks:folders')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('remove_from_folder_title','refworks')));
    refworks_base::write_footer();
    exit;
}


if ($userok == 0) {
    //check user is OK with removing ref

    //setup hidden fields for yes/no forms
    $yesfields = array('userok'=>2, 'sesskey'=>sesskey(), 'refer'=>$refer);
    if (refworks_base::$isinstance) {
        $yesfields['id'] = refworks_base::$cm->id;
    }

    $nofields = array('userok'=>1, 'cancelurl'=>$refer, 'refer'=>$refer);
    if (refworks_base::$isinstance) {
        $nofields['id'] = refworks_base::$cm->id;
    }
    global $OUTPUT;
    echo $OUTPUT->confirm(get_string('sure_remove_folder','refworks'),
        new moodle_url('', $yesfields), new moodle_url('', $nofields));


}else if ($userok == 2) {
    //remove ref
    confirm_sesskey();

    $result = false;

    $result = refworks_folder_api::remove_ref_from_folder($rid, $fld);

    if ($result === false) {
        throw new moodle_exception('removefromfolder_error', 'refworks', $refer, refworks_base::return_foldername($fld,true));
        //refworks_base::write_error(get_string('removefromfolder_error','refworks',refworks_base::return_foldername($fld,true)));
    } else {
        notify(get_string('removefromfolder_success','refworks',refworks_base::return_foldername($fld,true)),'notifysuccess');
    }

    redirect($refer);

}
refworks_base::write_footer();

//if (refworks_base::$isinstance) {
//    add_to_log(refworks_base::$course->id,'refworks','view','removeref.php?id='.refworks_base::$cm->id,'Access RefWorks reference remove from folder',refworks_base::$cm->id);
//}

?>