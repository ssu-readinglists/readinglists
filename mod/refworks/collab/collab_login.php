<?php
/**
 * Logs user into a shared team account. Connects to db tables refworks_collab_*
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../refworks_base.php');

$accid=required_param('accid',PARAM_INT);

refworks_base::init();
$curbreadcrumb = array(array('name' => get_string('team_loggingin', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

//check capability
if (!refworks_base::check_capabilities('mod/refworks:collaboration')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('team_loggingin','refworks')));
    refworks_base::write_footer();
    exit;
}
global $USER, $CFG, $DB;
refworks_collab_lib::get_user_accounts($USER->id);
//check user has permission to access $accid account
if (!refworks_collab_lib::can_user_access_account($accid)) {
    refworks_base::write_error(get_string('team_loginnotallowed','refworks'));
    refworks_base::write_footer();
    exit;
}

//attempt login - use details from db
if (!$accdetails = $DB->get_record('refworks_collab_accs', array('id' => $accid))) {
    refworks_base::write_footer();
    exit;
}
//log out of any existing session
rwapi::destroy_session();

//setup proxy access for all calls to rwapi
if ($CFG->proxyhost!='') {
    rwapi::$proxy=$CFG->proxyhost;
    if ($CFG->proxyport!='') {
        rwapi::$proxyport=$CFG->proxyport;
    }
}

$result = rwapi::check_session('',$accdetails->login,$accdetails->password);

//show result
if ($result) {
    //set $SESSION->rwteam to acc id number so we always know we are in a team acc
    global $SESSION;
    $SESSION->rwteam = $accid;
}

refworks_connect::require_login();

//Do sidemenu after account logging in as it reflects that we are logged in or not
//$emboldenedlink = 'collab/collab_login.php?accid='.$accid;
//refworks_base::write_sidemenu($emboldenedlink);
refworks_base::write_sidemenu();

//Main content goes here

refworks_base::write_heading(get_string('team_loggingin', 'refworks'));

//show result
if ($result) {
    notify(get_string('team_loginsuccess','refworks'),'notifysuccess');
    //if (refworks_base::$isinstance) {
    //    add_to_log(refworks_base::$course->id,'refworks','view','collab/collab_login.php?id='.refworks_base::$cm->id.'&accid='.$accid,'Logged into team account',refworks_base::$cm->id);
    //}

    //update db with login time
    refworks_collab_lib::update_login_time($accid);

} else {
    //error creating session
    refworks_base::write_error(get_string('team_loginerror','refworks'));
    //if (refworks_base::$isinstance) {
    //    add_to_log(refworks_base::$course->id,'refworks','view','collab/collab_login.php?id='.refworks_base::$cm->id.'&accid='.$accid,'Failure to log into team account',refworks_base::$cm->id);
    //}
}

refworks_connect::write_login_errors();

refworks_base::write_footer();
?>