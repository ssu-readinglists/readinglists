<?php
/**
 * Class for connecting to refworks api and associated module functionality
 * This can only be called from a page that has included refworks_base
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../../local/references/rwapi/rwapi.php');

class refworks_connect extends rwapi{

    //Use this var to check that connection is all ok before any calls
    public static $connectok = false;

    private static $errormsg = '';

    private static $successmsg = '';

    private static $showlogin = false;
    // Changed  'showcreateacc' to false for SSU. 27/03/2012. owen@ostephens.com
    private static $showcreateacc = false;

    /**
     * Main function to login to RefWorks
     * Provides alternate login options if fails
     * @param $override bool: Internal - used to stop looping when logging in after account creation
     * @return
     */
    public static function require_login($override=false) {
        //api call debugging on
        //parent::$debug = true;

        GLOBAL $USER, $CFG, $SESSION, $DB;
        //check overall system permission to allow connection to refworks
        if (!refworks_base::check_capabilities('mod/refworks:connect')) {
            return;
        }
        //check if trying to create account, if so do that and skip this
        $acccreate = optional_param('newacc',NULL,PARAM_BOOL);
        if ($acccreate!==NULL && !$override) {
            self::start_create_account();
            return;
        }
        //try to check/create a session
        //get the users email, if not submitted alternate or used alternate before, use stored moodle details
        //check if submitted alternate
        $logname = optional_param('name',NULL,PARAM_TEXT);
        $logpass = optional_param('pass',NULL,PARAM_TEXT);

        if ($logname!==NULL || $logpass!==NULL) {
            //login from form with alt details
            if (!confirm_sesskey()) {
                print_error('confirmsesskeybad');
            }
            $email = '';
            //check both filled in
            if ($logname==NULL || $logname=='' || $logpass==NULL || $logpass=='') {
                //refworks_base::write_error(get_string('alt_login_missing','refworks'));
                //self::write_login_form(false);
                self::$errormsg = get_string('alt_login_missing','refworks');
                self::$showlogin = true;
                self::$showcreateacc = false;
                return;
            }
        } else {

            if (isset($SESSION->rwalt) && $SESSION->rwalt==true) {
                $email = '';
                if (isset($SESSION->rwteam)) {
                    //user logged into a team account, check and add details so can auto re-login if session timeout
                    refworks_collab_lib::get_user_accounts($USER->id);
                    if (refworks_collab_lib::can_user_access_account($SESSION->rwteam)) {
                        if ($accdetails = $DB->get_record('refworks_collab_accs', array('id' => $SESSION->rwteam))) {
                            $logname = $accdetails->login;
                            $logpass = $accdetails->password;
                        }
                    }
                }
            } else {
                //use moodle email
                if (isset($USER->email)) {
                    $email = $USER->email;
                    //remove xxx address xxx from email as added by OU moodle
                    if (strpos($email,'xxx')===0 && (strrpos($email,'xxx')==(strlen($email)-3))) {
                        $email=substr($email,3,(strlen($email)-6));
                    }
                    if (!validate_email($email)) {
                        //refworks_base::write_error(get_string('invalid_email','refworks',$email));
                        //self::write_login_form(false);
                        self::$errormsg = get_string('invalid_email','refworks',$email);
                        self::$showlogin = true;
                        self::$showcreateacc = false;
                        return;
                    }
                } else {
                    //can't find any email
                    $email = '';
                }
            }
        }
        $success = false;

        //error code - used to work out what to present to user
        //0:untrapped
        //1:connection error
        //2:account invalid
        //3.session expired
        $error = 0;
        //if email looks ok attempt to create session in rwapi

        if ($logname!=NULL && $logpass!=NULL) {
            //check session - create session with alternate login details
            $success=parent::check_session($email,$logname,$logpass);
        } else {
            //check session standard way
            $success=parent::check_session($email);
        }

        //if fail, why?
        if (!$success) {
            //check connection
            if (parent::$lasterror == 'Error connecting to RefWorks API') {
                $error = 1;
            }else if (strpos(parent::$lasterror,'accounts with email') || parent::$lasterror == 'email address not found in group code') {
                $error = 2;
            }else if (parent::$lasterror == 'Invalid - groupCode, LoginName or password combination') {
                $error = 2;
            }else if (isset($SESSION->rwalt) && $SESSION->rwalt==true) {
                if (parent::$lasterror == 'No session available and no login details.') {
                    $error = 3;
                }
            }
        }

        //show user why fail
        if (!$success) {
            switch($error) {
                case 1:
                    //refworks_base::write_error(get_string('connection_error','refworks'));
                    self::$errormsg = get_string('connection_error','refworks');
                    break;
                case 2:
                    //refworks_base::write_error(get_string('account_login_error','refworks'));
                    //if account error, so error and present alternate login option/create account
                    //self::write_login_form();
                    self::$errormsg = get_string('account_login_error','refworks');
                    self::$showlogin = true;
                    break;
                case 3:
                    //refworks_base::write_error(get_string('account_login_expire','refworks'));
                    //if account error, so error and present alternate login option/create account
                    //self::write_login_form(false);
                    self::$errormsg = get_string('account_login_expire','refworks');
                    self::$showlogin = true;
                    self::$showcreateacc = false;
                    break;
                default:
                    //refworks_base::write_error(get_string('general_error','refworks'));
                    self::$errormsg = get_string('general_error','refworks');
                    break;
            }
        } else {
            //everything was OK, so record this
            self::$connectok = true;

            //if alternate email acc was submitted set $SESSION->rwemail
        }
    }

    /**
     * Writes any errors associated with require_login to the page
     * This is separate so you can control where the errors will appear
     * @return
     */
    public static function write_login_errors() {
        global $OUTPUT;
        if (self::$errormsg != '') {
            refworks_base::write_error(self::$errormsg);
        }else if (self::$successmsg !='') {
            echo $OUTPUT->notification(self::$successmsg, 'notifysuccess');
        }
        if (self::$showlogin) {
            self::write_login_form(self::$showcreateacc);
        }
    }

    private static function start_create_account() {
        GLOBAL $USER, $CFG, $_SERVER;
        //check sess key
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad');
        }
        //get cur email and validate
        //use moodle email
        if (isset($USER->email)) {
            $email = $USER->email;
            //remove xxx address xxx from email as added by OU moodle
            if (strpos($email,'xxx')===0 && (strrpos($email,'xxx')==(strlen($email)-3))) {
                $email=substr($email,3,(strlen($email)-6));
            }
            if (!validate_email($email)) {
                //refworks_base::write_error(get_string('invalid_email','refworks',$email));
                //self::write_login_form();
                self::$errormsg = get_string('invalid_email','refworks',$email);
                self::$showlogin = true;
                return;
            }
        }
        $usertype = 5;
        //OU specific get user id from SAMS and turn into refworks athens login
        if (isset($_SERVER['HTTP_SAMS_USER'])) {
            if ($_SERVER['HTTP_SAMS_STUDENTPI']!='00000000') {
                $loginname = $_SERVER['HTTP_SAMS_STUDENTPI'];
                $usertype = 1;
            }else if ($_SERVER['HTTP_SAMS_STAFFID']!='00000000') {
                $loginname = $_SERVER['HTTP_SAMS_STAFFID'];
                $usertype = 3;
            }else if ($_SERVER['HTTP_SAMS_TUTORID']!='00000000') {
                $loginname = $_SERVER['HTTP_SAMS_TUTORID'];
                $usertype = 3;
            }else if ($_SERVER['HTTP_SAMS_VISITORID']!='00000000') {
                $loginname = $_SERVER['HTTP_SAMS_VISITORID'];
            } else {
                $loginname = 'id'.substr(sha1($email),0,10);
            }
        } else {
            //random login name - hash of email
            $loginname = 'id'.substr(sha1($email),0,10);
        }
        //create random password
        $password = generate_password();
        $password = str_replace('&','*',$password);

        $result=parent::check_session('temp');
        //call rwapi to make account
        if ($usertype!=5) {
            //SAMS login - use athens login name
            $result = parent::create_account($loginname, $password, $USER->firstname.' '.$USER->lastname, $email, $usertype, true);
        } else {
            $result = parent::create_account($loginname, $password, $USER->firstname.' '.$USER->lastname, $email, $usertype);
        }

        //give user feedback
        if ($result==false) {
            //refworks_base::write_error(get_string('account_create_error','refworks',parent::$lasterror));
            //self::write_login_form();
            self::$errormsg = get_string('account_create_error','refworks',parent::$lasterror);
            self::$errormsg.= get_string('account_create_error_alt','refworks');

            self::$showlogin = true;
        } else {
            self::$successmsg = get_string('account_create_success','refworks');
            if (refworks_base::$config->refworks_shownewlogin == 1) {
                self::$successmsg .= '<p>'.get_string('account_create_details', 'refworks', array('name' => $loginname, 'pass' => $password)).'</p>';
            }
            //if ok - call require_login again (with override) to create session
            self::require_login(true);
        }

    }

    private static function write_login_form($createacc=true) {
		// Moved account creation instructions to lang file. 27/03/2012. owen@ostephens.com
		echo get_string('account_create_instructions','refworks'); 
        //create account button
        if ($createacc) {
            $mform = new refworks_newacc_form();
            $form=$mform->_formref;
            if (refworks_base::$isinstance) {
                $form->addElement('hidden', 'id', refworks_base::$cm->id);
            }
            $mform->display();
        }
        print('<br />');
        //create alternate login form
		// This would offer the ability to login with alternative RefWorks account
		// SSU Do not wish this to show ever. Commented out 27/03/2012 by owen@ostephens.com
		/*
        $mform = new refworks_login_form();
        $form=$mform->_formref;
        if (refworks_base::$isinstance) {
            $form->addElement('hidden', 'id', refworks_base::$cm->id);
        }
        $mform->display();
		*/
    }

}

//LOGIN FORMS

/**
 * Displays an account login form to allow for alternate account
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

global $CFG;


require_once($CFG->libdir.'/formslib.php');

class refworks_login_form extends moodleform {

    public $_formref;
    function definition() {

        global $COURSE, $SESSION;
        $mform    =& $this->_form;
        $this->_formref = &$this->_form;

        //-------------------------------------------------------------------------------
        // Add General box
        $mform->addElement('header', 'general', get_string('acc_form_head', 'refworks'));

        $atts = array('size'=>'32');
        if (isset($SESSION->rwlogin)) {
            //Fill field with any existing accepted login name in this session
            $atts['value'] = $SESSION->rwlogin;
        }

        $mform->addElement('text', 'name', get_string('acc_form_name', 'refworks'), $atts);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('password', 'pass', get_string('acc_form_pass', 'refworks'), array('size'=>'32'));
        $mform->setType('pass', PARAM_TEXT);
        $mform->addRule('pass', null, 'required', null, 'client');
        $mform->addRule('pass', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        //-------------------------------------------------------------------------------
        // Add Save Changes and Cancel buttons
        $this->add_action_buttons(false,get_string('acc_form_submit','refworks'));
    }
}

class refworks_newacc_form extends moodleform {
    public $_formref;

    function definition() {

        global $COURSE, $USER;
        $mform    =& $this->_form;
        $this->_formref = &$this->_form;

        //-------------------------------------------------------------------------------
        // Add General box
        $mform->addElement('header', 'general2', get_string('newacc_form_head', 'refworks'));

        $mform->addElement('hidden', 'newacc', 'true');
        // Add Save Changes and Cancel buttons
        $this->add_action_buttons(false,get_string('newacc_form_submit','refworks'));
    }
}

?>