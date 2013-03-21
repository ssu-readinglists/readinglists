<?php
/**
 * (Static)Library class for collaborative functionality in refworks module
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks/collab
 */

class refworks_collab_lib {
    public static $accsavail = array();//Array of accounts users has access to: gets populated by get_user_accounts()
    //accounts in these arrays take the format of object ->id, ->name
    public static $accsown = array();//Array of accounts user has 'owner' permission over: gets populated by get_user_owned_accounts()
    public static $dormaccs = array();//Array of all dormant accounts
    /**
     * Returns array of accounts user has been given permission to access
     * Also sets this value to $accsavail - so can be re-accessed without db call
     * @param $userid - moodle userid of user
     * @return array
     */
    public static function get_user_accounts($userid, $update=false) {
        if (!$update && isset(self::$accsavail[0])) {
            //already done, no need to re-access db unless forced $update
            return self::$accsavail;
        }
        global $DB;

        $sql = "SELECT accs.id, name FROM {refworks_collab_accs} accs INNER JOIN {refworks_collab_users} users ON users.userid = ? AND accs.id = users.accid WHERE accs.datedeleted IS NULL ORDER BY name";
        if (!$accs = $DB->get_records_sql($sql, array($userid))) {
            return array();
        }

        self::$accsavail = $accs;
        return $accs;
    }

    /**
     * Returns array of accounts that user has 'owner' status
     * Also sets this value to $accsown - so can be re-accessed without db call
     * @param $userid
     * @return array
     */
    public static function get_user_owned_accounts($userid) {

        global $CFG, $DB;

        if (!$accs = $DB->get_records_sql("SELECT accs.* FROM {refworks_collab_accs} accs INNER JOIN {refworks_collab_users} users ON users.userid = ? AND users.owner=1 AND accs.id = users.accid WHERE accs.datedeleted IS NULL ORDER BY name", array($userid))) {
            return array();
        }

        self::$accsown = $accs;
        return $accs;
    }

    /**
     * Returns array of all account records in the database
     * @return array
     */
    public static function get_all_accounts() {
        global $CFG, $DB;

        //if (!$result=get_records('refworks_collab_accs','','','name','id,name,created,lastlogin')) {
        if (!$result = $DB->get_records_sql("SELECT * FROM {refworks_collab_accs} WHERE datedeleted IS NULL")) {
            return array();
        } else {
            return $result;
        }
    }

    /**
     * Returns array of all dormant account records in the database
     * @return array
     */
    public static function get_all_dormant_accounts($update=false) {
        if (!$update && isset(self::$dormaccs[0])) {
        //already done, no need to re-access db unless forced $update
        return self::$dormaccs;
        }
        global $CFG, $DB;

        if (!$dormaccs = $DB->get_records_sql("SELECT id,name,login,created,lastlogin,datedeleted FROM {refworks_collab_accs} WHERE datedeleted IS NOT NULL ORDER BY name")) {
            return array();
        }
        self::$dormaccs = $dormaccs;
        return $dormaccs;
    }

    /**
     * Checks if user can access a particular account id
     * @param $accid int: accounts id in db table refworks_collab_accs
     * @return bool
     */
    public static function can_user_access_account($accid) {
        foreach (self::$accsavail as $acc) {
            if ($acc->id == $accid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if user can administer a particular account id
     * @param $accid int: accounts id in db table refworks_collab_accs
     * @return bool
     */
    public static function can_user_own_account($accid) {
        foreach (self::$accsown as $acc) {
            if ($acc->id == $accid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Updates the db field lastlogin for account to record time of last login
     * @param $accid int: account id
     * @return
     */
    public function update_login_time($accid) {
        global $DB;
        $DB->set_field('refworks_collab_accs','lastlogin',time(), array('id' => $accid));
    }

    /**
     * Returns array of user rows (from user id) that are in account
     * @param $accid int: accounts id in db table refworks_collab_accs
     * @param $who int: 0-all, 1-owners, 2-non-owners
     * @param $excludeself bool: default excludes current user from list
     * @return array
     */
    public static function get_participants($accid, $who=0, $excludeself=true) {
        global $USER, $CFG, $DB;
        $ownercheck = -1;
        if ($who==1) {
            $ownercheck = 1;
        }else if ($who==2) {
            $ownercheck = 0;
        }

        global $CFG;

        //whether results inc cur user...
        if ($excludeself) {
            $exclude = "AND usertable.id <> $USER->id";
        } else {
            $exclude = "";
        }

        if ($ownercheck == -1) {
            //get all users on this acc
            if (!$result=$DB->get_records_sql("SELECT usertable.id,firstname,lastname,username FROM {user} usertable INNER JOIN {refworks_collab_users} users ON users.accid = ? AND users.userid = usertable.id $exclude ORDER BY firstname", array($accid))) {
                return array();
            }
        } else {
            //only get users who are/n't owners
            if (!$result=$DB->get_records_sql("SELECT usertable.id,firstname,lastname,username FROM {user} usertable INNER JOIN {refworks_collab_users} users ON users.accid = ? AND users.owner = ? AND users.userid = usertable.id $exclude ORDER BY firstname", array($accid, $ownercheck))) {
                return array();
            }
        }

        return $result;
    }

    /**
     * Adds a new user to the account
     * @param $accid int: accounts id in db table refworks_collab_accs
     * @param $userid
     * @param $asowner
     * @return
     */
    public static function add_participant($accid, $userid, $asowner=0) {
        global $DB;
        $newrec = new stdClass();
        $newrec->userid=$userid;
        $newrec->accid=$accid;
        $newrec->owner=$asowner;

        if ($DB->record_exists('refworks_collab_users', array('userid' => $userid, 'accid' => $accid))) {
            $existrec=$DB->get_record('refworks_collab_users', array('userid' => $userid, 'accid' => $accid));
            $updaterec = new stdClass();
            $updaterec->id = $existrec->id;
            $updaterec->owner=$asowner;
            $DB->update_record('refworks_collab_users',$updaterec);
        } else {
            $DB->insert_record('refworks_collab_users',$newrec);
        }
    }

    public static function remove_participant($accid, $userid) {
        global $DB;
        $DB->delete_records('refworks_collab_users', array('userid' => $userid, 'accid' => $accid));
    }

    /**
     * Permanently delete a shared account
     * @param $accid
     * @return true or false
     */
    public static function delete_account($accid) {
        global $DB;
        $userdelete = $DB->delete_records('refworks_collab_users', array('accid' => $accid));
        $collabsdelete = $DB->delete_records('refworks_collab_accs', array('id' => $accid));
        if ($userdelete==false||$collabsdelete==false) {
            return false;
        }
        return true;
    }

    /**
     * Returns the record for the account
     * @param $accid
     * @return record object
     */

    public static function get_account_details($accid) {
        global $DB;
        return $DB->get_record('refworks_collab_accs', array('id' => $accid));
    }
    /**
     * Rename an account
     * @param $id int: account id
     * @param $newname string: proposed acount name
     * @param $loginonly bool: true if you want to keep display name to original
     * @return success
     */
    public static function rename_account_details($id,$newname,$loginonly=false) {
        global $DB;
        $login = strtolower(str_replace(' ','_',$newname));
        //check if an account already exists with the proposed new login
        if ($DB->get_record('refworks_collab_accs', array('login' => $login))) {
            refworks_base::write_error(get_string('team_createacc_exists','refworks'));
        } else {
            $rec = new stdClass();
            $rec->id=$id;
            if (!$loginonly) {
                $rec->name=$newname;
            }
            $rec->login=addslashes(htmlspecialchars($login,ENT_COMPAT,'utf-8',false));
            return $DB->update_record('refworks_collab_accs',$rec);
        }
        return false;
    }

    /**
     * Make an account dormant
     * @param $id int: account id
     * @return success
     */
    public static function make_account_dormant($id) {
        global $DB;
        $rec = new stdClass();
        $rec->id=$id;
        $rec->datedeleted=time();
        return $DB->update_record('refworks_collab_accs',$rec);
    }

    /**
     * Reactivate a dormant account
     * @param $id int: account id
     * @return success
     */
    public static function reactivate_dormant_account($id) {
        global $DB;
        $rec = new stdClass();
        $rec->id=$id;
        $rec->datedeleted=NULL;
        return $DB->update_record('refworks_collab_accs',$rec);
    }
}
?>