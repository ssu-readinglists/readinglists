<?php
/**
 * Class for connecting to refworks api for reference related calls
 * RefWorks session must be ok (refworks_connect) when using this class
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../../local/references/rwapi/rwapi.php');

class refworks_folder_api extends rwapi{

    //List of the users folders (so no need to always call api)
    //Always update this using update_folder_list if expected change
    //Contains folder name and number of refs inside
    //Multi-dimensional array - array(array('name' , 'numrefs'))
    public static $folders = array();
    //List of the users shared folders
    public static $sharedfolders = array();

    /**
     * Updates the static $folders array with list of users folder names
     * CAN be called without calling any previous api authentication (will return false if no session avail)
     * @return success
     */
    public static function update_folder_list() {
        if (refworks_connect::$connectok) {
            $result = parent::call_api('folders','all',array('pgnum'=>1,'pgsize'=>1000));
            if (!$result) {
                return false;
            }
            $resxml=new domDocument();
            $resxml->loadXML($result);
            $folderlist = $resxml->getElementsByTagName('Folders');

            self::$folders = array();
            for ($a=0, $max=$folderlist->length; $a<$max; $a++) {
                if ($folderlist->item($a)->getAttribute('type') == 'user') {
                    self::$folders[] = array('name'=>$folderlist->item($a)->nodeValue, 'numrefs'=>$folderlist->item($a)->getAttribute('nRefs'));
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public static function add_ref_to_folder($rid, $fld) {
        $result = parent::call_api('folders','addref',array('folder'=>$fld,'id'=>$rid));
        return $result;
    }

    public static function remove_ref_from_folder($rid, $fld) {
        $result = parent::call_api('folders','removeref',array('folder'=>$fld,'id'=>$rid));
        return $result;
    }

      /**
     * Receives a string and creates a new folder in Refworks with that string value
     * @param $name
     * @return $result
     */
    public static function create_folder($name) {
        $name=str_replace(',',' ',$name);//api doesn't seem to like ,
        $name=str_replace(';',' ',$name);//illegal
        $name=str_replace('|',' ',$name);//illegal
        $param = array('newValue'=>$name);
        $result = parent::call_api('folders','create',$param);
        if ($result == false) {
            return false;
        }
        return true;
    }
  /**
     * Deletes all references that are in a folder
     * @param $refarray array:array of ref id(s)
     * @return success
     */
    public static function delete_folder_refs($refarray) {
        $deletexml = '<RWRequest class="reference" method="delete">';
        foreach ($refarray as $bits) {
            if ($bits!='' && $bits!= 0) {
                $deletexml.= '<id>'.$bits.'</id>';
            }
        }
        $deletexml.= '</RWRequest>';
        $result = parent::call_api('reference','delete','',$deletexml);
        if ($result == false) {
            return false;
        }
        return true;
    }
      /**
     * Deletes a folder
     * @param $foldername string: foldername
     * @return success
     */
    public static function delete_folder_only($foldername) {
        $param = array('oldValue'=>$foldername);
        $result = parent::call_api('folders','delete',$param);
        if ($result == false) {
            return false;
        }
        return true;
    }
      /**
     * Renames a folder
     * @param $oldfoldername string: old folder name
     * @param $newfoldername string: new folder name
     * @return success
     */
    public static function rename_folder($oldfoldername,$newfoldername) {
        $newfoldername=str_replace(',',' ',$newfoldername);//api doesn't seem to like ,
        $newfoldername=str_replace(';',' ',$newfoldername);//illegal
        $newfoldername=str_replace('|',' ',$newfoldername);//illegal
        $param = array('oldValue'=>refworks_base::return_foldername($oldfoldername),'newValue'=>refworks_base::return_foldername($newfoldername));
        $result = parent::call_api('folders','edit',$param);
        if ($result == false) {
            return false;
        }
        return true;
    }

     /**
     * Gets all the shared RefShare folders
     * @param $oldfoldername string: old folder name
     * @return $result: xml string
     */
    public static function get_refshares() {
        $result = parent::call_api('shareproperties','all');
        $resxml=new domDocument();
        $resxml->loadXML($result);
        $xpath=new DOMXPath($resxml);
        $sharefoldersxml = $xpath->query('//share');
        self::$sharedfolders = array();
        foreach ($sharefoldersxml as $xmlitem) {
            $folderTag = $xmlitem->getElementsByTagName("folder");
            $folder = $folderTag->item(0)->nodeValue;
            $shareurlpathTag = $xmlitem->getElementsByTagName("shareurlpath");
            $shareurlpath = $shareurlpathTag->item(0)->nodeValue;
            self::$sharedfolders[] = array('folder'=>$folder,'shareurlpath'=>$shareurlpath);
        }
        return self::$sharedfolders;
    }

     /**
     * deletes (removes) the refshare folder(s)
     * @param $foldername string: folder name
     * @return success
     */
    public static function remove_refshares($folder = '') {
        if ($folder!='') {
            $param = array('sharedfolder'=>refworks_base::return_foldername($folder));
        }
        $result = parent::call_api('shareproperties','delete',$param);
        if ($result == false) {
            return false;
        }
        return true;
    }

     /**
     * adds (shares/publishes) a folder (or folders)
     * @param $foldername string: folder name
     * @return success
     */
    public static function add_refshares($folder = '') {
        global $USER;
        $email = '[local data - email address]';
        if (isset($USER->email)) {
            $email = $USER->email;
        }
        $addxml = '<RWRequest class="shareproperties" method="add">';
        //$addxml.= '<shareproperties total = "1">';
        $addxml.= '<share><title>'.htmlentities($folder).'_share</title>';
        $addxml.= '<allowexport>1</allowexport>';
        $addxml.= '<allowprint>1</allowprint>';
        $addxml.= '<allowgenbib>1</allowgenbib>';
        $addxml.= '<allowdisplayofcustomos>true</allowdisplayofcustomos>';
        $addxml.= '<allowauthoremail>true</allowauthoremail>';
        $addxml.= '<allowattachments>true</allowattachments>';
        $addxml.= '<allowcomments>false</allowcomments>';
        $addxml.= '<allowfeeds>true</allowfeeds>';
        $addxml.= '<emailcomments>false</emailcomments>';
        $addxml.= '<displayosfavs>true</displayosfavs>';
        $addxml.= '<displaysubcriberos>true</displaysubcriberos>';
        $addxml.= '<displayglobalos>true</displayglobalos>';
        $addxml.= '<rssfeedgeneration type="3">50 Most Recently Added References</rssfeedgeneration>';
        $addxml.= '<openurl type="0">Site Defined OpenURL</openurl>';
        $addxml.= '<insitearea>true</insitearea>';
        $addxml.= '<authoremail>'.$email.'</authoremail>';
        $addxml.= '<category></category>';
        $addxml.= '<info/>';
        $addxml.= '<folder>'.htmlentities(refworks_base::return_foldername($folder)).'</folder></share>';
        //$addxml.= '</shareproperties>';
        $addxml.= '</RWRequest>';
        $result = parent::call_api('shareproperties','add','',$addxml);
        if ($result == false) {
            return false;
        }
        return true;
    }

}

?>