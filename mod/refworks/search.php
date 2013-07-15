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
require_once(dirname(__FILE__).'/search_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/../../local/references/getdata.php');
global $CFG, $OUTPUT;
require_once($CFG->libdir.'/filelib.php');

refworks_base::init();

//work out where we came from
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

//check if get data button has been pressed
$getdata = optional_param('get_data','none',PARAM_TEXT);
$doivalue = optional_param('s_do','',PARAM_TEXT);
$snvalue = optional_param('s_sn','',PARAM_TEXT);
error_log($getdata);
if($doivalue) {
	$getref = references_getdata::call_crossref_api($doivalue);
}

if($snvalue) {
	$getref = references_getdata::call_primo_api($snvalue,'isbn','0');
}


$curbreadcrumb = array(array('name' => get_string('search', 'refworks'), 'type' => 'refworks'));
$heading = get_string('search', 'refworks');

refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();

refworks_base::write_sidemenu();

//Main content goes here
refworks_base::write_heading($heading);

search_base::search_form();

refworks_base::write_footer();

?>