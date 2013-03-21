<?php
/**
 * Main class to connect to and maintain session with refworks api
 * Other rwaip classes can extend this class to get it's functionality
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package rwapi
 */
require_once(dirname(__FILE__).'/../references_lib.php');
global $CFG;
require_once($CFG->libdir.'/filelib.php');

class rwapi {

    private static $acceskeyid;

    private static $secretkey;

    protected static $groupcode;

    //The group id is used in Athens related PUIDs
    private static $groupid;

    protected static $sessionid='';

    public static $proxy;//DEPRECATED IN MOODLE 2

    public static $proxyport;//DEPRECATED IN MOODLE 2

    public static $lasterror='';//Text explanation of the last error encountered in process

    protected static $debug=false;

    private static $curl;

    /**
     * Checks if there is already a a valid rw session in this session
     * If not will attempt to create one for the user email account or login details sent
     * @param $email string: (optional)recommended - need to send if you want to create account
     * @return success
     */
    public static function check_session($email='',$login='',$password='') {
        global $SESSION;
        //populates sessionid with a stored rw session id
        //This is stored in the moodle $SESSION var along with the last call time - rw sessions expire after 20 mins and email used
        //$SESSION->rwsess - the refworks session id
        //$SESSION->rwtime - the time of the last call (session expires after 20 mins)
        //$SESSION->rwuser - the user associated with the session (their email or temp if a temporary account)
        //$SESSION->rwalt - set to true if logged in with acc details (info not stored, so once session expires re-login required)
        //$SESSION->rwlogin - last login name used (for manual login)
        //Else it will make a session for the user with the email/details specified
        //If no email or no stored session makes sessionid empty, returns false

        self::$lasterror='';
        if (isset($SESSION->rwsess)) {
            self::$sessionid=$SESSION->rwsess;
            //now check if email and expires time are ok
            if (isset($SESSION->rwtime)) {
                if ((time()-$SESSION->rwtime)>=(20*60)) {
                    //last call was over 20 mins ago - expired
                    self::$sessionid='';
                }
            } else {
                //In theory should never happen...
                self::$sessionid='';
            }
            if (self::$sessionid!='' && $email!='' && isset($SESSION->rwuser)) {
                //check email matches
                if ($email!=$SESSION->rwuser) {
                    self::$sessionid='';
                }
            }
        }

        if (self::$sessionid!='') {
            //we have a valid session id
            return true;
        }

        if ($email=='' && $login=='' && $password=='') {
            //we need an email or temp or details to make a session
            self::error('No session available and no login details.');
            return false;
        }

        self::load_settings();

        //try and make a session
        if (strtolower($email)=='temp') {
            //make a temporary user session
            $result=self::call_api('authentication','newtempusersess');
            if ($result==false) {
                return false;
            }

            $resxml=new domDocument();
            $resxml->loadXML($result);

            $callinfo=$resxml->getElementsByTagName('CallInfo');

            if ($callinfo->length==0) {
                self::error('Error getting temp user session.');
                return false;
            }

            self::$sessionid=$callinfo->item(0)->getAttribute('sess');
            $SESSION->rwalt = false;
        }else if ($email!='') {
            //get all users with this email
            self::destroy_session();

            //get the user based on email address
            $result=self::call_api('user','userbyemail',array('groupcode'=>self::$groupcode,'email'=>$email));
            if ($result==false) {
                //also returns false if no account at all
                return false;
            }
            $resxml=new domDocument();
            $resxml->loadXML($result);
            $loginlist=$resxml->getElementsByTagName('userLogin');
            $useacc = 0;
            if ($loginlist->length!=1) {
                $useacc = -1;
                //if not 1 account with that name
                //check if SAMS login - if so check if any of the accounts match
                $loginname = '';
                global $_SERVER;
                if (isset($_SERVER['HTTP_SAMS_USER'])) {
                    if ($_SERVER['HTTP_SAMS_STUDENTPI']!='00000000') {
                        $loginname = $_SERVER['HTTP_SAMS_STUDENTPI'];
                    }else if ($_SERVER['HTTP_SAMS_STAFFID']!='00000000') {
                        $loginname = $_SERVER['HTTP_SAMS_STAFFID'];
                    }else if ($_SERVER['HTTP_SAMS_TUTORID']!='00000000') {
                        $loginname = $_SERVER['HTTP_SAMS_TUTORID'];
                    }else if ($_SERVER['HTTP_SAMS_VISITORID']!='00000000') {
                        $loginname = $_SERVER['HTTP_SAMS_VISITORID'];
                    }
                    $loginname = self::create_puid_from_athens($loginname);
                    for ($a=0,$max=$loginlist->length;$a<$max;$a++) {
                        if ($loginlist->item($a)->nodeValue == $loginname) {
                            $useacc = $a;
                        }
                    }
                }

                if ($useacc == -1) {
                    //no account match
                    self::error($loginlist->length.' accounts with email '.$email);
                    return false;
                }
            }
            //create session

            //get the users login details so we can make session
            //we need loginname <userLogin>, groupcode , password<userPassword>
            $userlogin=$loginlist->item($useacc)->nodeValue;
            $userpass=htmlentities($resxml->getElementsByTagName('userPassword')->item($useacc)->nodeValue);

            if (self::create_session($userlogin, $userpass)) {
                $SESSION->rwalt = false;
            } else {
                return false;
            }

        }else if ($login!='' and $password!='') {
            //check user exists
            $sessxml='<RWRequest class="authentication" method="userexists">'.
            '<AcctInfo loginName="'.$login.'" groupCode="'.self::$groupcode.'" password="'.$password.'">'.
            '</AcctInfo></RWRequest>';

            $result=self::call_api('authentication','userexists','',$sessxml);

            if ($result==false) {
                return false;
            }

            //success - user exists create new session
            if (self::create_session($login, $password)) {
                $SESSION->rwalt = true;
                $SESSION->rwlogin = $login;
            } else {
                return false;
            }
        } else {
            //should never get here as trapped earlier
            return false;
        }

        //if all ok add info to session vars
        $SESSION->rwsess=self::$sessionid;
        $SESSION->rwtime=time();
        $SESSION->rwuser=$email;
        return true;
    }

    /**
     * Creates a session and adds to static sessionid var
     * @param $userlogin
     * @param $userpass
     * @return success
     */
    private static function create_session($userlogin, $userpass) {
        self::load_settings();
        $sessxml='<RWRequest class="authentication" method="newsess">'.
            '<AcctInfo loginName="'.$userlogin.'" groupCode="'.self::$groupcode.'" password="'.$userpass.'">'.
            '</AcctInfo></RWRequest>';

        $result=self::call_api('authentication','newsess','',$sessxml);

        if ($result==false) {
            return false;
        }

        $resxml=new domDocument();
        $resxml->loadXML($result);

        $callinfo=$resxml->getElementsByTagName('CallInfo');

        if ($callinfo->length==0) {
            self::error('Error getting session id from RefWorks.');
            return false;
        }

        self::$sessionid=$callinfo->item(0)->getAttribute('sess');
        return true;
    }

    public static function destroy_session() {
        //kill session
        if (self::$sessionid) {
            self::call_api('authentication','delsess');
            self::$sessionid = '';
        }
        GLOBAL $SESSION;
        unset($SESSION->rwsess);
        unset($SESSION->rwtime);
        unset($SESSION->rwuser);
        unset($SESSION->rwalt);
        unset($SESSION->rwlogin);
        unset($SESSION->rwteam);
    }

    /**
     * Saves the references sent into the current refworks session account
     * @param $refxml string:xml string of reference xml in refworks format (rwxml)
     * @param $email string (optional):email of user to add refs to (default: will use cur session)
     * @param $folder string (optional:folder to add into)
     * @return success
     */
    public static function add_references($refxml, $email='',$folder='') {
        $result=self::check_session($email);

        if ($result==false) {
            return false;
        }

        $send='<RWRequest class="reference" method="add"><RWRefData>';
        $send.=$refxml;
        $send.='<RWRefData/></RWRequest>';

        $params='';
        if ($folder!='') {
            $params=array('folder'=>$folder);
            //check folder exists
            $allfolders = self::get_user_foldernames();
            if ($allfolders == false) {
                $allfolders = array();
            }
            if (!in_array($folder, $allfolders)) {
                self::call_api('folders','create',array('newValue'=>$folder));
            }
        }

        $result=self::call_api('reference','add',$params,$refxml);

        if ($result==false) {
            return false;
        } else {
            return true;
        }
    }

    public static function get_user_foldernames() {
        $result = self::call_api('folders','all',array('pgnum'=>1,'pgsize'=>1000));
        if (!$result) {
            return false;
        }
        $resxml=new domDocument();
        $resxml->loadXML($result);
        $folderlist = $resxml->getElementsByTagName('Folders');

        $folders = array();
        for ($a=0, $max=$folderlist->length; $a<$max; $a++) {
            if ($folderlist->item($a)->getAttribute('type') == 'user') {
                $folders[] = $folderlist->item($a)->nodeValue;
            }
        }
        return $folders;
    }

    /**
     * Creates a new user account
     * @param $login string:login id
     * @param $password string
     * @param $username string:Full name
     * @param $email string
     * @param $usertype int
     * @param $athens bool:set to true to create athens login name
     * @return success
     */
    public static function create_account($login, $password, $username, $email, $usertype, $athens=false) {
        self::load_settings();
        if ($athens) {
            $login = self::create_puid_from_athens($login);
        }
        $userxml = '<RWRequest class="user" method="create">'.
        '<AcctInfo loginName="'.$login.'" groupCode="'.self::$groupcode.'" password="'.$password.'">'.
        '<userName>'.$username.'</userName>'.
        '<eMail>'.$email.'</eMail>'.
        '<userDemographics uType="'.$usertype.'" focus="7"/>'.
        '</AcctInfo>'.
        '</RWRequest>';

        $result = self::call_api('user','create','',$userxml);
        return $result;
    }

    /**
     * Deletes user account
     * @param $login string:login id
     * @param $password string
     * @return success
     */
    public static function delete_account($login, $password) {
        self::load_settings();
        $userxml = '<RWRequest class="user" method="delete">'.
        '<AcctInfo loginName="'.$login.'" groupCode="'.self::$groupcode.'" password="'.$password.'" />'.
        '</RWRequest>';
        $result = self::call_api('user','delete','',$userxml);
        return $result;
    }

    /**
     * Main function that calls the refworks api
     * @param $class string:class name you wish to call
     * @param $method string:method to call in class
     * @param $params array:associative array of url params 'param'=>'value' (optional)
     * @param $xml string: xml to post (optional)
     * @return success
     */
    protected static function call_api($class,$method,$params='',$xml='') {
        global $CFG;

        self::load_settings();

        self::$lasterror='';

        if ($params=='') {
            $params=array();
        }

        $url='http://www.refworks.com/api2/';
        /*if ($class=='authentication' && $method=='newsess') {
            //use ssl to send user data
            $url='https://www.refworks.com/api2/';
        }*/
        $url.='?class='.$class;
        $url.='&method='.$method;

        if (self::$sessionid!='') {
            $url.='&sess='.self::$sessionid;
        }

        //add params based on array key/value
        foreach ($params as $key => $val) {
            $url.="&$key=".urlencode($val);
        }

        $expires=self::get_expires();
        $url.='&expires='.$expires;

        $url.='&accesskeyid='.self::$acceskeyid;

        $url.='&signature='.self::get_signature($class,$expires);
        $starttime=round(microtime(true),4);
        if (!isset(self::$curl)) {
            self::$curl = new curl();
        }

        $options = array(
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_CONNECTTIMEOUT' => 15,
            'CURLOPT_HTTPPROXYTUNNEL' => false
        );
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $options['CURLOPT_USERAGENT'] = $_SERVER['HTTP_USER_AGENT'];
        }

        if ($xml != '') {
            $options['CURLOPT_HTTPHEADER'] = array('Expect:');
            //compress string by removing whitespace
            //$xml=preg_replace('/\s\s+/', '', trim($xml));
            //remove any xml declararion as no needed
            $xml=str_replace('<?xml version="1.0" encoding="utf-8"?>','',$xml);
            $page = self::$curl->post($url, $xml, $options);
        } else {
            //GET
            $page = self::$curl->get($url, array(), $options);
        }



        $endtime=round(microtime(true),4);

        self::debug('API Call ('.(substr(($endtime - $starttime),0,4))." secs): $url\r".html_entity_decode($page));

        if ($page!=false) {
            //correct response will always have result of 200, check for this
            if (strpos($page,'result="200"')===false) {
                //work out what the error was and return details
                if (strpos($page,'<refworks')!==false) {
                    $resxml=new domDocument();
                    $resxml->loadXML($page);
                    if ($resxml->getElementsByTagName('RWResult')->length>0) {
                        self::error($resxml->getElementsByTagName('RWResult')->item(0)->getAttribute('resultMsg'));
                    } else {
                        self::error('RefWorks did not return an expected response. No valid error msg returned.');
                    }
                } else {
                    self::error('RefWorks did not return an expected response');
                }
                if ($method=='refattach') {
                    return $page;//if downloading file contents send contents back now (an error would make $page=false)
                }
                return false;
            }
        } else {
            self::error('Error connecting to RefWorks API');
        }

        return $page;
    }

    //Returns an expiry time of 10 mins from present in milliseconds
    private static function get_expires() {
        $expires=time()+(10 * 60);//make expires time 10 mins ahead
        $expires=$expires * 1000;//turn from seconds to milliseconds
        return $expires;
    }

    //generates and returns the encoded signature that refworks uses to authenticate call
    private static function get_signature($class,$expires) {
        /*The Signature is created by concatenating <class of call>+ <AccessKeyID> + <expires>
         * and then running the result through a Base64 encoding of the HMAC-SHA1,
         * passing it   <secret Access Key> as the encoding key*/

        $string=$class.self::$acceskeyid.$expires;

        $crypt = hash_hmac('sha1', trim($string), self::$secretkey);
        $binary_hmac = pack("H40", $crypt);

        $hash=base64_encode($binary_hmac);

        return urlencode($hash);
    }

    /**
     * Converts an Athens id into a RefWorks personal user id (login id)
     * @param $identity string: MUST be string - Athens ID
     * @return string: RefWorks PUID
     */
    private static function create_puid_from_athens($identity) {
        self::load_settings();
        $identity = strval($identity);

        //Test for student identifier
        //Inital letter must be converted to int (based on hex, so a=10,b=11...)
        $letters = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
        $initlet=array_search(substr($identity,0,1),$letters);
        if ($initlet!==false) {
            //first char is a letter, convert to 2 digit number
            $replacenum = 10 + $initlet;
            //If the last character of the person id is an X - as is sometimes required by the check digit process - then the X is replaced by 0 and 37 is added is added to the numeric representation of the first character.
            if (substr($identity,strlen($identity)-1)=='X') {
                $identity = substr_replace($identity,'0',strlen($identity)-1);
                $replacenum = $replacenum + 37;
            }
            $identity = substr_replace($identity,strval($replacenum),0,1);
        }

        $dec=dechex($identity);
        $padded=str_pad($dec,8,0,STR_PAD_LEFT);
        $hex=strtolower($padded);
        $result = '';
        for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2) {
            $result .= substr($hex, $x, 2);
        }
        return 'PUID-'.$result.':'.self::$groupid;
    }

    private static function error($text) {
        self::$lasterror = $text;
        self::debug($text);
        //add Moodle logging to record errors
        global $COURSE;
        if (function_exists('add_to_log')) {
            $courseid=0;
            if (isset($COURSE->id) && $COURSE->id != SITEID) {
                $courseid = $COURSE->id;
            }
            $cururl = $_SERVER['SCRIPT_NAME'];
            if (isset($_SERVER['QUERY_STRING'])) {
                $cururl .= '?'.$_SERVER['QUERY_STRING'];
            }
            $cururl = substr($cururl, 0, 100);
            add_to_log($courseid,'rwapi','error',$cururl,$text);
        }
    }

    private static function debug($string) {
        if (self::$debug) {
            error_log($string);
        }
    }

    /**
     * Loads settings from plugin config and loads into class static vars
     * Will only make the db call once
     */
    private static function load_settings() {
        if (!isset(self::$acceskeyid)) {
            //Set everything
            self::$acceskeyid = references_lib::get_setting('accesskeyid');
            self::$secretkey = references_lib::get_setting('secretkey');
            self::$groupcode = references_lib::get_setting('groupcode');
            self::$groupid = references_lib::get_setting('groupid');
        }
    }
}
?>