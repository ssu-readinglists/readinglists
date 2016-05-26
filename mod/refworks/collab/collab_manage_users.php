<?php
/**
 * Manage users in a shared team account.
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../refworks_base.php');

$accid = required_param('accid',PARAM_INT);

refworks_base::init();
$curbreadcrumb = array(array('name' => get_string('team_manageacc', 'refworks'), 'type' => 'refworks', 'link'=>refworks_base::return_link('collab/collab_manage.php')),array('name' => get_string('team_manage_permission_acc', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

//check capability
if (!refworks_base::check_capabilities('mod/refworks:collaboration_createacc')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('team_manage_permission_acc','refworks')));
    refworks_base::write_footer();
    exit;
}

refworks_connect::require_login();

//Do sidemenu after account logging in as it reflects that we are logged in or not

refworks_base::write_sidemenu('collab/collab_manage.php');

//Main content goes here
$accdetails = refworks_collab_lib::get_account_details($accid);

refworks_base::write_heading(get_string('team_manage_permission_accname', 'refworks', $accdetails->name));

/*refworks_connect::write_login_errors();
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}*/

//Check user has owner permission over account
global $USER;
refworks_collab_lib::get_user_owned_accounts($USER->id);
if (!refworks_collab_lib::can_user_own_account($accid) && !refworks_base::check_capabilities('mod/refworks:collaboration_admin')) {
    refworks_base::write_error(get_string('team_manage_acc_deny','refworks'));
    refworks_base::write_footer();
    exit;
}

//Include the invite code
require_once(dirname(__FILE__).'/collab_manage_invite.php');

//if (refworks_base::$isinstance) {
//    add_to_log(refworks_base::$course->id,'refworks','view','collab/collab_manage_users.php?id='.refworks_base::$cm->id,'Manage acess to team account',refworks_base::$cm->id);
//}

refworks_base::write_footer();
?>