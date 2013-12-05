<?php
/**
 * Export to RefWorks account, using API and DirectExport
 * Attempts to find users RefWorks account by email using api - if has this adds references
 * If no account show form that re-submits and the will create account (start_create_account())
 * If couldn't add using api, show form that submits using Direct Export - users can login to alternate accounts using this method
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package convert
 */
require_once(dirname(__FILE__).'/rwxml.php');
require_once(dirname(__FILE__).'/../rwapi/rwapi.php');

class references_convert_refworks_directexport extends references_convert_format{

    public function __construct() {

    }

    public static function can_import() {
        return false;
    }

    public static function can_export() {
        return true;
    }

    public function import(&$data) {
    }

    public static function is_format(&$data) {
        return false;
    }

    //Exports to mystuff by calling portfolio/moodel/reference/savetoportfolio.php
    public function export(&$data,$options) {
        //if $data is a string turn into xml dom
        if (is_string($data)) {
            $data=parent::string_to_dom($data);
        }

        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }

        //check if we want refs in a folder here
        $infolder=optional_param('reffolder','',PARAM_TEXT);
        $whatfolder=optional_param('folder','',PARAM_TEXT);

        //make data in refworks xml format
        $refwxml=new rwxml();
        $result=$refwxml->export($data,null);
        if ($result===false) {
            return false;
        }
        if (isset($options->unittest)) {
            return true;
        }
        global $USER, $CFG;
        //rwapi
        //setup quikbib class with proxy that moodle is using
        if ($CFG->proxyhost!='') {
            rwapi::$proxy=$CFG->proxyhost;
            if ($CFG->proxyport!='') {
                rwapi::$proxyport=$CFG->proxyport;
            }
        }
        if (isset($USER->email)) {
            $email = $USER->email;
            //remove xxx address xxx from email as added by OU moodle
            if (strpos($email,'xxx')===0 && (strrpos($email,'xxx')==(strlen($email)-3))) {
                $email=substr($email,3,(strlen($email)-6));
            }

            //selected to create an account
            $createacc = optional_param('createacc','',PARAM_TEXT);
            if ($createacc != '') {
                //setup account details, use SAMS if available to generate a puid
                $callresult = self::start_create_account();
            }

            //Try and create a RefWorks session with email
            $callresult = rwapi::check_session($email);
            //if fail - is because no account? - if so create one

            if ($callresult!==false) {
                $fromajax=false;
                if (isset($options->ajax)) {
                    if ($options->ajax=='true') {
                        $fromajax=true;//called here from an ajax related page, so don't allow folder option
                    }
                }
                if (!$fromajax && $infolder=='') {
                    //show folder form (reffolder option)

                    //first get list of folders
                    $folders=rwapi::get_user_foldernames();
                    if (count($folders)>0) {
                        $formy='<form name="cerate_acc" method="post" class="mform">';
                        //re-create all the post vars again, so on submit will return to same state
                        foreach ($_POST as $post=>$postval) {
                            $formy.='<input name="'.$post.'" type="hidden" value="'.htmlspecialchars($postval).'" />';
                        }
                        if (isset($options->redirect) && !isset($_POST['referer'])) {
                            $formy.='<input name="referer" type="hidden" value="'.$options->redirect.'" />';
                        }
                        //show folders
                        $formy.='<div><DIV class="fitemtitle"><LABEL for="folder">'.get_string('refexport_rwde_selfolder','local_references').'</LABEL></DIV><DIV class="felement fselect"><SELECT id="folder" name="folder"><OPTION selected value="">'.get_string('refexport_rwde_none','local_references').'</OPTION>';
                        foreach ($folders as $folder) {
                            $formy.='<OPTION value="'.htmlspecialchars($folder).'">'.stripslashes($folder).'</OPTION>';
                        }
                        $formy.='</SELECT></DIV>';
                        $formy.='<DIV><DIV class=fitem><DIV class=fitemtitle></DIV><DIV class="felement fsubmit"><INPUT id="reffolder" value="'.get_string('refexport_rwde_save','local_references').'" type="submit" name="reffolder"/></DIV></DIV></DIV>';
                        $formy.='<br/></div></form>';
                        echo $formy;
                    return true;
                    }
                }
                //Try and connect to an account + add refs
                if (rwapi::add_references($result->contents,$email,stripslashes(htmlspecialchars_decode($whatfolder)))) {
                    //if MyReferences available, show link to that
                    if (file_exists(dirname(__FILE__).'/../../../mod/refworks/view.php')) {
                        echo('<p><a href="'.$CFG->wwwroot.'/mod/refworks/view.php">'.get_string('modulename','refworks').'</a></p>');
                    }
                    echo('<p><a href="'.get_string('linktorefworks', 'local_references').'" target="RefWorksMain">'.get_string('refexport_rwde_linktoref','local_references').'</a></p>');
                    if (isset($options->redirect)) {
                        echo('<p><a href="'.urldecode($options->redirect).'">'.get_string('continue').'</a></p>');
                    }
                    return true;
                }
            }
        }
        //Couldn't add using refworks api - create account? or use direct export instead
        if (!$callresult && (strpos(rwapi::$lasterror,'0 accounts')!==false || rwapi::$lasterror == 'email address not found in group code')) {
            $formy='<form name="cerate_acc" method="post" class="mform">';
            //re-create all the post vars again, so on submit will return to same state
            foreach ($_POST as $post=>$postval) {
                $formy.='<input name="'.$post.'" type="hidden" value="'.$postval.'" />';
            }
            $formy.='<input type="submit" name="createacc" value="'.get_string('refexport_rwde_createacc','local_references').'" /></form>';
            echo $formy;
        }
        //hack to make refworks import accept &
        $result=htmlspecialchars($result->contents,ENT_NOQUOTES,'UTF-8',true);
        //$result=str_replace('&amp;','%26',$result);

        $formy='<div id="rwde">'.
        '<form name="ExportRWForm" id="ExportRWForm" class="mform" method="post" target="RefWorksMain" action="http://www.refworks.com/refworks/autologin.asp?vendor=The%20Open%20University&amp;filter=RefWorks%20XML&amp;encoding=65001">'.
        '<TEXTAREA name="ImportData" rows="30" cols="70" style="display:none">'.
        $result.
        '</TEXTAREA><br/>'.
        '<input type="submit" name="Submit" id="rwde_submit" value="'.
        get_string('refexport_rwde_submit','local_references').
        '"/>';
        if (isset($options->redirect)) {
            $formy.='<p><a href="'.urldecode($options->redirect).'">'.get_string('continue').'</a></p>';
        }
        $formy.='</form></div>';

        echo $formy;
        //if we get here and is an ajax call then need to return false so UI shows
        if (isset($options->ajax)) {
            if ($options->ajax=='true') {
                return false;
            }
        }
        return true;
    }

    private static function start_create_account() {
        GLOBAL $USER, $CFG, $_SERVER;
        //get cur email and validate
        //use moodle email
        if (isset($USER->email)) {
            $email = $USER->email;
            //remove xxx address xxx from email as added by OU moodle
            if (strpos($email,'xxx')===0 && (strrpos($email,'xxx')==(strlen($email)-3))) {
                $email=substr($email,3,(strlen($email)-6));
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

        //$result=parent::check_session('temp');
        //call rwapi to make account
        if ($usertype!=5) {
            //SAMS login - use athens login name
            $result = rwapi::create_account($loginname, $password, $USER->firstname.' '.$USER->lastname, $email, $usertype, true);
        } else {
            $result = rwapi::create_account($loginname, $password, $USER->firstname.' '.$USER->lastname, $email, $usertype);
        }

        //give user feedback
        if ($result==false) {
            return false;
        } else {
            return true;
        }

    }

}

?>