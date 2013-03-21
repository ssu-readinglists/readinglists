<?php
/**
 * Manage shared accounts.
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author j.ackland-snow@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../refworks_base.php');
require_once(dirname(__FILE__).'/../refworks_display.php');
require_once(dirname(__FILE__).'/../refworks_ref_api.php');

refworks_base::init();
$curbreadcrumb = array(array('name' => get_string('team_manageacc', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

//check capability
/*
if (!refworks_base::check_capabilities('mod/refworks:collaboration')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('team_createacc','refworks')));
    refworks_base::write_footer();
    exit;
}
*/

global $USER, $CFG, $OUTPUT, $DB;


class refworks_renamesharedaccount_form extends moodleform {

    public $_formref;

    function definition() {

        global $COURSE;
        $this->_formref = $this->_form;
        $nform    =& $this->_form;
        //-------------------------------------------------------------------------------
        // Add General box
        /// Adding the standard "name" field
        $nform->addElement('text', 'namey', get_string('team_new_account_name', 'refworks'), array('size'=>'64'));
        $nform->setType('namey', PARAM_TEXT);
        $nform->addRule('namey', null, 'required');
        $nform->addRule('namey', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $nform->addRule('namey', get_string('minimumchars','refworks', 3), 'minlength', 3, 'client');
        //-------------------------------------------------------------------------------
        // Add Save Changes and Cancel buttons
        $this->add_action_buttons(true,get_string('team_rename_account','refworks'));
    }
}

function common_prelimininaries() {
    //check session key if form actioned
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }
    /*refworks_connect::write_login_errors();
    if (!refworks_connect::$connectok) {
        //No RefWorks session? Do nothing instead.
        refworks_base::write_footer();
        exit;
    }*/

}
function common_postlimininaries() {
    refworks_base::write_sidemenu('collab/collab_manage.php');
    refworks_base::write_heading(get_string('team_manageacc', 'refworks'));
    //check capability
    if (!refworks_base::check_capabilities('mod/refworks:collaboration')) {
        refworks_base::write_error(get_string('nopermissions','error',get_string('team_manageacc','refworks')));
        refworks_base::write_footer();
        exit;
    }
    /*refworks_connect::write_login_errors();
    if (!refworks_connect::$connectok) {
        //No RefWorks session? Do nothing instead.
        refworks_base::write_footer();
        exit;
    }*/
    global $SESSION, $USER, $CFG;
}

refworks_connect::require_login();
$refer = refworks_base::return_link('collab/collab_manage.php');
$storedaccountno = 0;

//Main content goes here

// if form action
$account_id = optional_param('account_id',0,PARAM_INT);
$account_name = optional_param('account_name','',PARAM_TEXT);
$r_account_id = optional_param('r_account_id',0,PARAM_INT);
//$r_account_named = optional_param('r_account_name','',PARAM_TEXT);
$namey = optional_param('namey', 'unsubmitted', PARAM_TEXT);
$delete = optional_param('delete_account_x',-1,PARAM_INT);
$rename = optional_param('rename_account_x',-1,PARAM_INT);
$dodelete = optional_param('delete',0,PARAM_INT);
$dormant = optional_param('showdormant',0,PARAM_INT);

$restore = optional_param('restore_x',-1,PARAM_INT);
$permanentlydelete = optional_param('delete_x',-1,PARAM_INT);
$permdelete = optional_param('permdelete',0,PARAM_INT);

// mode selector
$pageselector = 'none';
if ($rename!=-1||$namey!='unsubmitted') {
    $pageselector = 'rename';
}elseif ($dodelete==2) {
    $pageselector = 'deletemessage';
}elseif ($delete!=-1) {
    $pageselector = 'delete';
}elseif ($restore!=-1) {
    $pageselector = 'restore';
}elseif ($permdelete==2) {
    $pageselector = 'permanentdeletemessage';
}elseif ($permanentlydelete!=-1) {
    $pageselector = 'permanentdelete';
}
$showrenameform = false;
$renameerror = '';

switch($pageselector) {
    case 'permanentdelete':
        common_prelimininaries();
        //setup hidden fields for yes/no forms
        $yesfields = array('permdelete'=>2, 'sesskey'=>sesskey(), 'refer'=>$refer, 'account_id'=>$account_id);
        if (refworks_base::$isinstance) {
            $yesfields['id'] = refworks_base::$cm->id;
        }
        $nofields = array('permdelete'=>1, 'sesskey'=>sesskey(), 'cancelurl'=>$refer, 'refer'=>$refer);
        if (refworks_base::$isinstance) {
            $nofields['id'] = refworks_base::$cm->id;
        }

        echo $OUTPUT->confirm(get_string('team_sure_delete_account','refworks', $account_name),
            new moodle_url('', $yesfields), new moodle_url('', $nofields));
        refworks_base::write_footer();
        exit;
        break;
    case 'permanentdeletemessage':
        // functionality to remve account from refworks (at time of writing no delete function available on refworks api TODO
        common_prelimininaries();
        //now get the account details to pass to the refworks api
        $accdetails = refworks_collab_lib::get_account_details($account_id);
        if ($accdetails==false) {
            common_postlimininaries();
            print_error('team_dbaccess_error','refworks');
            refworks_base::write_footer();
            exit;
        }
        $loginname = $accdetails->login;
        $password = $accdetails->password;
        //now delete the refworks account using the refworks api
        $result = rwapi::delete_account($loginname, $password);
        if (!$result) {
            refworks_base::write_error(get_string('team_deleteacc_error','refworks'));
        } else {
            //now delete the account details from the database
            $dbdelete = refworks_collab_lib::delete_account($account_id);
            if ($dbdelete==false) {
                common_postlimininaries();
                print_error('team_dbdelete_error','refworks');
                refworks_base::write_footer();
                exit;
            }
        }
        break;
    case 'delete':
        common_prelimininaries();
        //setup hidden fields for yes/no forms
        $yesfields = array('delete'=>2, 'sesskey'=>sesskey(), 'refer'=>$refer, 'account_id'=>$account_id);
        if (refworks_base::$isinstance) {
            $yesfields['id'] = refworks_base::$cm->id;
        }
        $nofields = array('delete'=>1, 'sesskey'=>sesskey(), 'cancelurl'=>$refer, 'refer'=>$refer);
        if (refworks_base::$isinstance) {
            $nofields['id'] = refworks_base::$cm->id;
        }

        echo $OUTPUT->confirm(get_string('team_sure_remove_account','refworks', htmlspecialchars(stripslashes($account_name))),
            new moodle_url('', $yesfields), new moodle_url('', $nofields));
        refworks_base::write_footer();
        exit;
        break;
    case 'deletemessage':
        common_prelimininaries();

        //Rename account, keep adding x to acc name until no match in db
        $accdetails = refworks_collab_lib::get_account_details($account_id);
        $newlogin = $accdetails->login;
        $exists = true;
        while ($exists!==false) {
            $newlogin = 'x_'.$newlogin;
            $exists = $DB->record_exists('refworks_collab_accs', array('login' => $newlogin));
        }
        //store any existing collab login
        if (isset($SESSION->rwteam)) {
            $storedaccountno = $SESSION->rwteam;
        }
        rwapi::destroy_session();
        //Connect to account and rename
        $result = rwapi::check_session('',$accdetails->login,$accdetails->password);
        //show result
        if ($result) {
            //set $SESSION->rwteam to acc id number so we always know we are in a team acc
            global $SESSION;
            $SESSION->rwteam = $r_account_id;
        } else {
            common_postlimininaries();
            print_error('team_update_error','refworks');
            refworks_base::write_footer();
            exit;
        }
        // now confirmed that we are in the correct shared account do the actual renaming (including login) of the shared account

        if (!$preresult = refworks_ref_api::update_account_login($newlogin)) {
            common_postlimininaries();
            print_error('team_update_error','refworks');
            refworks_base::write_footer();
            exit;
        }
        //save new login details to db
        $result = refworks_collab_lib::rename_account_details($account_id,$newlogin,true);
        // renaming completed, now see if we have stored shared account number we were in and therefore need to go back to
        rwapi::destroy_session();
        //re-login
        if (($storedaccountno != $account_id) && ($storedaccountno != 0)) {
            //attempt login - use details from db
            if (!$accdetails = $DB->get_record('refworks_collab_accs', array('id' => $storedaccountno))) {
                refworks_base::write_footer();
                exit;
            }
            $result = rwapi::check_session('',$accdetails->login,$accdetails->password);
            //show result
            if ($result) {
                //set $SESSION->rwteam to acc id number so we always know we are in a team acc
                global $SESSION;
                $SESSION->rwteam = $storedaccountno;
            }
        }
        refworks_connect::require_login();
        refworks_collab_lib::make_account_dormant($account_id);

        break;
    case 'rename':
        common_prelimininaries();
        $nform = new refworks_renamesharedaccount_form();
        if (optional_param('cancel', 0, PARAM_RAW)) {
            //you need this section if you have a cancel button on your form
            //here you tell php what to do if your user presses cancel
            //probably a redirect is called for!
            continue;
        }else if ($fromform=$nform->get_data()) { //data received from the rename form
            $accdetails = $DB->get_record('refworks_collab_accs', array('id' => $r_account_id));
            if (!$accdetails) {
                common_postlimininaries();
                print_error(get_string('team_account_profile_not_found','refworks'));
                refworks_base::write_footer();
                exit;
            }
            //before we do anything check the new name (is it same as the existing or another account)
            $newlogname = str_replace(' ','_',strtolower(addslashes(htmlspecialchars($namey))));
            if ($newlogname == $accdetails->login) {
                //same name as before, show warning and display form again
                $showrenameform = true;
                $renameerror = get_string('team_createacc_samename','refworks');
                continue;
            } else {
                //check login not already used elsewhere, if so give details and display form again
                if ($exists = $DB->record_exists('refworks_collab_accs', array('login' => $newlogname))) {
                    $showrenameform = true;
                    $accowners = refworks_collab_lib::get_participants($exists->id,1,false);
                    $ownerlist = '<ul>';
                    foreach ($accowners as $owner) {
                        $ownerlist .= '<li>'.$owner->firstname.' '.$owner->lastname.'</li>';
                    }
                    $ownerlist .= '</ul>';
                    $renameerror = get_string('team_createacc_exists','refworks',$ownerlist);
                    continue;
                }
            }
            if (isset($SESSION->rwteam)) {
                $storedaccountno = $SESSION->rwteam;
            }
            rwapi::destroy_session();

            $result = rwapi::check_session('',$accdetails->login,$accdetails->password);
            //show result
            if ($result) {
                //set $SESSION->rwteam to acc id number so we always know we are in a team acc
                global $SESSION;
                $SESSION->rwteam = $r_account_id;
            } else {
                common_postlimininaries();
                print_error('team_update_error','refworks');
                refworks_base::write_footer();
                exit;
            }
            // now confirmed that we are in the correct shared account do the actual renaming (including login) of the shared account

            if (!$preresult = refworks_ref_api::update_account_login($namey)) {
                common_postlimininaries();
                print_error('team_update_error','refworks');
                refworks_base::write_footer();
                exit;
            }
            $result = refworks_collab_lib::rename_account_details($r_account_id,$fromform->namey);
            // renaming completed, now see if we have stored shared account number we were in and therefore need to go back to
            rwapi::destroy_session();
            if (($storedaccountno != $r_account_id) && ($storedaccountno != 0)) {
                //attempt login - use details from db
                if (!$accdetails = $DB->get_record('refworks_collab_accs', array('id' => $storedaccountno))) {
                    refworks_base::write_footer();
                    exit;
                }
                $result = rwapi::check_session('',$accdetails->login,$accdetails->password);
                //show result
                if ($result) {
                    //set $SESSION->rwteam to acc id number so we always know we are in a team acc
                    global $SESSION;
                    $SESSION->rwteam = $storedaccountno;
                }
            }
            refworks_connect::require_login();
            continue;
        } else { //initial view of the rename form
            $showrenameform = true;
        }
        break;
    case 'restore':
        common_prelimininaries();
        refworks_collab_lib::reactivate_dormant_account($account_id);
        break;
    default:
        ;
}
if ($showrenameform) {
    //shared code to display the rename form
    $form=$nform->_formref;

    //populate text field with existing account name
    $options['namey'] = stripslashes($account_name);
    $form->setDefaults($options);

    $form->addElement('hidden', 'r_account_id', $account_id);
    $form->addElement('hidden', 'r_account_name', $account_name);
    $form->addElement('hidden', 'refer', $refer);
    if (refworks_base::$isinstance) {
        $form->addElement('hidden', 'id', refworks_base::$cm->id);
    }
    common_postlimininaries();
    if ($renameerror != '') {
        refworks_base::write_error($renameerror);
    }
    $nform->display();
    refworks_base::write_footer();
    exit;
}

common_postlimininaries();

//Default view
?>
<div class="instructions">
<p>This page allows you to manage shared accounts you own. You can access
accounts from the <em>Shared accounts</em> section of the side-menu.</p>
<p>As the owner of the account you have agreed to take
responsibility for the administration of the account including
management of the membership of the account and the content which is added to
the account.</p>
<h2>Responsibilities as Owner</h2>
<p>You will be expected to:</p>
<ol>
    <li>Manage membership: you will be able to add new members or
    remove a member's access to the account, and only the people you invite
    into the account will be able to access it (with the exception of some
    administrative staff).</li>
    <li>Delete the account when you no longer use it.</li>
</ol>
</div>
<?php
//check whether admin or not and display shared account information accordingly
if (refworks_base::check_capabilities('mod/refworks:collaboration_admin')) {
    $accounts = refworks_collab_lib::get_all_accounts();
    $dormantaccounts = refworks_collab_lib::get_all_dormant_accounts();
    if (count($accounts)>0) {
        refworks_display::write_shared_accounts_list($accounts, refworks_display::viewaction);
    }
    if (count($dormantaccounts)>0) {
        refworks_display::write_shared_accounts_list($dormantaccounts, refworks_display::viewdormantaction);
    }
} else {
    $ownaccounts = refworks_collab_lib::get_user_owned_accounts($USER->id);
    if (count(refworks_collab_lib::$accsown)>0) {
        refworks_display::write_shared_accounts_list(refworks_collab_lib::$accsown, refworks_display::viewaction);
    } else {
        echo $OUTPUT->box(get_string('team_no_accounts','refworks'));
    }
}
refworks_base::write_footer();
?>