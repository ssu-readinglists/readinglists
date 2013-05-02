<?php
/**
 * Create a shared team account.
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../refworks_base.php');

refworks_base::init();
$curbreadcrumb = array(array('name' => get_string('team_createacc', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

//check capability
if (!refworks_base::check_capabilities('mod/refworks:collaboration_createacc')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('team_createacc','refworks')));
    refworks_base::write_footer();
    exit;
}

global $USER, $CFG, $DB;

refworks_connect::require_login();

//Do sidemenu after account logging in as it reflects that we are logged in or not

refworks_base::write_sidemenu('collab/collab_create.php');

//Main content goes here

refworks_base::write_heading(get_string('team_createacc', 'refworks'));


/*refworks_connect::write_login_errors();
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}*/

////////////////////////////////////////////
class mod_form_createacc extends moodleform {

    public $_formref;

    function definition() {
        $this->_formref = $this->_form;
        $mform =& $this->_form;
        //-------------------------------------------------------------------------------
        /// Adding the "general" fieldset, where all the common settings are showed

        /// Adding the standard "name" field
        $mform->addElement('text', 'namey', get_string('team_newaccname', 'refworks'), array('size'=>'64'));
        $mform->setType('namey', PARAM_TEXT);
        $mform->addRule('namey', null, 'required');
        $mform->addRule('namey', get_string('maximumchars', '', 40), 'maxlength', 40, 'client');
        $mform->addRule('namey', get_string('minimumchars','refworks', 3), 'minlength', 3, 'client');

        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons(false,get_string('team_createacc','refworks'));
    }
}
////////////////////////////////////////

$mform = new mod_form_createacc();
if ($fromform=$mform->get_data()) {
    //check session key if form actioned
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }
    //try and create account (login is copy of accname, no spaces), password is random, username is shared team account, email is vle@open.ac.uk
    $login = strtolower(str_replace(' ','_',$fromform->namey));
    //add the user name to acc login to make name unique to user
    if (isset($USER->username)) {
        $login = '[['.str_replace(' ','_',htmlspecialchars($USER->username)).']]'.$login;
    }
    $pass = generate_password().'!aZ3$';
    $pass = str_replace('&','*',$pass);

    //before trying to create account, check no conflicts with existing
    if ($exists = $DB->record_exists('refworks_collab_accs', array('login' => addslashes(htmlspecialchars($login))))) {
        //show list of acc owners
        $accowners = refworks_collab_lib::get_participants($exists->id,1,false);
        $ownerlist = '<ul>';
        foreach ($accowners as $owner) {
            $ownerlist .= '<li>'.$owner->firstname.' '.$owner->lastname.'</li>';
        }
        $ownerlist .= '</ul>';
        refworks_base::write_error(get_string('team_createacc_exists','refworks',$ownerlist));
    } else {
        $result = rwapi::create_account(htmlspecialchars($login),$pass,'Shared team account',get_config('mod_refworks')->refworks_collabacemail,5);
        //give feedback, if ok store to db, show link to permission manage and then exit
        if (!$result) {
            refworks_base::write_error(get_string('team_createacc_error','refworks'));
        } else {
            notify(get_string('team_createacc_success','refworks'),'notifysuccess');

            //update db with new account
            $newacc = new stdClass();
            $newacc->name = $fromform->namey;
            $newacc->login = addslashes(htmlspecialchars($login));
            $newacc->password = $pass;
            $newacc->created = time();

            $newaccid = $DB->insert_record('refworks_collab_accs',$newacc,true);

            global $USER;
            //update db with account owner
            $newuser = new stdClass();
            $newuser->userid = $USER->id;
            $newuser->accid = $newaccid;
            $newuser->owner = 1;

            $DB->insert_record('refworks_collab_users',$newuser,false);

            //link to manage account users
            $idlink = refworks_base::$isinstance ? '&amp;id=' . refworks_base::$cm->id : '';
            print('<p class="continuebutton"><a href="collab_manage_users.php?accid='.$newaccid.$idlink.'" >'.get_string('team_manage_permission_acc','refworks').'</a></p>');
            //link to login (as doesn't appear on side menu until another page accessed)
            print('<p class="continuebutton"><a href="collab_login.php?accid='.$newaccid.$idlink.'" >'.get_string('team_login','refworks').' '.stripslashes($newacc->name).'</a></p>');
            refworks_base::write_footer();
            exit;
        }
    }
}

//Default view
?>
<div class="instructions">
<p>This form allows you to create a new shared account. You can access
accounts from the <em>Shared accounts</em> section of the side-menu.</p>
<p>By initiating this shared account, you are agreeing to take
responsibility for the administration of the account as owner - you agree
to manage the membership of the account and the content which is added to
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
if (refworks_base::$isinstance) {
    $form=$mform->_formref;
    $form->addElement('hidden', 'id', refworks_base::$cm->id);
}
$mform->display();


if (refworks_base::$isinstance) {
    add_to_log(refworks_base::$course->id,'refworks','view','collab/collab_create.php?id='.refworks_base::$cm->id,'Create a team account',refworks_base::$cm->id);
}

refworks_base::write_footer();
?>