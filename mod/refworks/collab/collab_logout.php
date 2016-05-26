<?php
/**
 * Logs user out of a shared team account (and back into own account).
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../refworks_base.php');

refworks_base::init();
$curbreadcrumb = array(array('name' => get_string('team_logout', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

//check capability
if (!refworks_base::check_capabilities('mod/refworks:collaboration')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('team_logout','refworks')));
    refworks_base::write_footer();
    exit;
}

global $USER, $CFG;

//log out of any existing session
rwapi::destroy_session();

refworks_connect::require_login();

//Do sidemenu after account logging in as it reflects that we are logged in or not

refworks_base::write_sidemenu();

//Main content goes here

refworks_base::write_heading(get_string('team_logout', 'refworks'));

notify(get_string('team_logoutsuccess','refworks'),'notifysuccess');

refworks_connect::write_login_errors();

//if (refworks_base::$isinstance) {
//    add_to_log(refworks_base::$course->id,'refworks','view','collab/collab_logout.php?id='.refworks_base::$cm->id,'Log out of team account',refworks_base::$cm->id);
//}

refworks_base::write_footer();
?>