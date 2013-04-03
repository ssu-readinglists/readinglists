<?php
/**
 * Connects to the RefWorks API using a single account to emulate the functionality of quikbib
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local/references/apibib
 */

require_once(dirname(__FILE__).'/../rwapi/rwapi.php');
require_once(dirname(__FILE__).'/../convert/refxml.php');
require_once(dirname(__FILE__).'/../references_lib.php');


class apibib extends rwapi{

    //Array of supported reference style names
    //string - string used
    //quikbib - The full name as it is known in RefWorks
    //id - the id of the output style (you can get this from preview styles in RefWorks)
    public static $referencestyles=array(
    array('string'=>'Open University (Harvard)','quikbib'=>'Open University (Harvard)','id'=>0),
    array('string'=>'Open University (Faculty of Health & Social Care)','quikbib'=>'Open University (Faculty of Health & Social Care)','id'=>0),
    array('string'=>'MHRA','quikbib'=>'MHRA-Modern Humanities Research Association (Notes & Bibliography)','id'=>315),
    array('string'=>'MLA','quikbib'=>'MLA 7th Edition','id'=>1571),
    array('string'=>'Open University (Resource List)','quikbib'=>'Open University (Resource List)','id'=>0)
    );

    /**
     * Returns the reference styles - either default or from a setting
     * Use this instead of $referencestyles (makes a db call...)
     */
    public static function get_referencestyles() {
        if (references_lib::get_setting('referencestyles') != '') {
            return unserialize(references_lib::get_setting('referencestyles'));
        } else {
            return self::$referencestyles;
        }
    }

    /**
     * Removes all references from temp Refworks account that have not been modified for 30 minutes
     * Then removes all empty folders
     * Used by cron to clear down RefWorks temp account
     */
    public static function cleartemp() {
		GLOBAL $SESSION;
        $return = "";
		//kill any previous api sessions
		parent::destroy_session();
		$rwun = references_lib::get_setting('tempun');
        $rwpw = references_lib::get_setting('temppw');
        if (!parent::check_session('',$rwun,$rwpw)) {
			//failed
            $return .= "Unable to authenticate for RefWorks temp account";
            return $return;
		}

        // Get all references and look for those older than an hour
        $params = array('pgnum'=>1,'pgsize'=>1000,'style'=>0);
        $result = parent::call_api('retrieve','all',$params);
        // What if $result is false?
        if ($result!==false) {
            $resxml=new domDocument();
            $resxml->loadXML($result);
            //get xml element that contains the references
            $referencelist = $resxml->getElementsByTagName('reference');
            $references = array();
            // Ugly time hack required because RW returns US Eastern Time converted to "epoch time" rather than actual epoch time
            $dt = new DateTime(NULL, new DateTimeZone('America/New_York'));
            $rwclockoff = $dt->getOffset();
            for ($a=0, $max=$referencelist->length; $a<$max; $a++) {
                $modifiedtime = $referencelist->item($a)->getElementsByTagName('md')->item(0)->nodeValue;
                $id = $referencelist->item($a)->getAttribute('id');
                $mod_time = $modifiedtime/1000+$rwclockoff;
                $timer = microtime(true);
                $age = $timer-$mod_time;
                if($age > 1800) {
                    $ids[] = $id;
                }
            }
            // Create XML of reference IDs for deletion
            $xml = '';
            if (count($ids) > 0) {
                $xml .= '<RWRequest class="reference" method="get">';
                foreach ($ids as $id) {
                    $xml .= '<id>'.$id.'</id>';
                }
                $xml .= '</RWRequest>';
            }

            if (strlen($xml) > 0) {
                //    Need to send this xml for deletion
                $result = parent::call_api('reference','delete','',$xml);
                if ($result!==false) {
                    $return .= "Deleted ".count($ids)." references."; 
                } else {
                    $return .= "Could not delete references.";
                }
            } else {
                $return .= "Did not find any references to delete";
            }
            // Now have deleted references older than one day, time to clean up any empty folders
            // Get all folders
            $result = parent::call_api('folders','all',array('pgnum'=>1,'pgsize'=>1000));
            if (!$result) {
                return false;
            }
            $resxml=new domDocument();
            $resxml->loadXML($result);
            $folderlist = $resxml->getElementsByTagName('Folders');
            $folders = array();
            // Only interested in folders that have references in them
            for ($a=0, $max=$folderlist->length; $a<$max; $a++) {
                if ($folderlist->item($a)->getAttribute('type') == 'user' && $folderlist->item($a)->getAttribute('nRefs') == '0') {
                    $folders[] = $folderlist->item($a)->nodeValue;
                }
            }

            if ($folders == false) {
                $folders = array();
            }
            
            foreach ($folders as $folder) {
                parent::call_api('folders','delete',array('oldValue'=>$folder));
            }
            $return .= "Tried to delete ".count($folders)." empty folders";
        } else {
            $return .= "Could not retrieve any references from RefWorks temp account";
        }
	return $return;
	}

    /**
     * Replaces the functionality of quikbib
     * Takes a reference file (string) and gets bibliography of titles
     * @param $references string: String of ref data matching filter
     * @param $style string: name of 'string' of required reference style form $referencestyles
     * @param $filter string: name of import filter e.g. RIS FORMAT
     * @param $proxy string: name of proxy to use (optional)
     * @param $proxyport in: number of proxy port (optional)
     * @return success or array of titles
     */
    public static function getbib($references, $style, $filter, $proxy='', $proxyport='', $replacetags=true) {
        GLOBAl $SESSION;

        //kill any previous api sessions
        parent::destroy_session();

        // encoding of string to submit - make utf-8
        if (mb_detect_encoding($references, "UTF-8, ASCII, ISO-8859-1")!="UTF-8") {
            $references=utf8_encode($references);
        }

        //try and make a temp session
        $rwun = references_lib::get_setting('tempun');
        $rwpw = references_lib::get_setting('temppw');
        if (!parent::check_session('',$rwun,$rwpw)) {
            //failed
			error_log("Failed to start session with RefWorks temp login");
            return false;
        }

        //store a random folder name for creation
        $folder = 'apibib_';
        GLOBAl $USER;
        if (isset($USER->username)) {
            $folder .= $USER->username;
        } else {
            $folder .= 'TEMP';
        }
        $folder .= mt_rand(0,500);

        //Test if folder exists, if not create, if so clear (should of been deleted anyway)
        $allfolders = parent::get_user_foldernames();
        if ($allfolders == false) {
            $allfolders = array();
        }

        if (in_array($folder, $allfolders)) {
            parent::call_api('folders','clear',array('folder'=>$folder));
        } else {
            parent::call_api('folders','create',array('newValue'=>$folder));
        }


        //work out the filter id (support RIS + XML) for import
        if (stripos($filter,'RIS')!==false) {
            $refxml = new refxml();
            //convert RIS to XML
            $references = $refxml->return_transform_in($references,'RIS');
            //transform to api xml
            $references = $refxml->return_references($references,true);
            unset($refxml);
        }else if (stripos($filter,'RefWorks')!==false) {
            $refxml = new refxml();
            //convert RWXML to XML
            $references = $refxml->return_transform_in($references,'RefWorksXML');
            //transform to api xml
            $references = $refxml->return_references($references,true);
            unset($refxml);
        } else {
			error_log("Failed to work out appropriate filter ID");
            return false;
        }
        $references = str_replace('<?xml version="1.0" encoding="utf-8"?>','',$references);
        $references = str_replace(' xmlns:refworks="www.refworks.com/xml/"', '',$references);
        //import refs into temp account -need to use "add" method as import has issues
        $send='<RWRequest class="reference" method="add"><RWRefData>';
        $send.=$references;
        $send.='</RWRefData></RWRequest>';
        //$result = parent::call_api('reference','import',$params,$references);
        $params = array();
        $params['folder'] = $folder;

        $result = parent::call_api('reference','add',$params,$send);
        if (!$result) {
			error_log("Failed to upload and/or retrieve styled references from RefWorks");
            return false;
        }

        //work out the style id from $referencestyles - if 0 then search from api
        //using the stylename get the id from $referencestyles
        $referencestyles = self::get_referencestyles();
        $returnedstyle = -1;
        for ($a=0,$max=count($referencestyles);$a<$max;$a++) {
            if ($referencestyles[$a]['string']==$style) {
                $returnedstyle = $referencestyles[$a]['id'];
                //set the name to the "correct" refworks value
                $style = $referencestyles[$a]['quikbib'];
            }
        }
        //use default if none found
        if ($returnedstyle == -1) {
            $returnedstyle = $referencestyles[0]['id'];
            $style = $referencestyles[0]['quikbib'];
        }

        //if id is 0 - search for name with api
        if ($returnedstyle == 0) {
            $result = parent::call_api('outputstyle','search',array('search'=>$style));
            if (!$result) {
				error_log("Could not find style via RefWorks API");
                return false;
            }
            $resxml=new domDocument();
            $resxml->loadXML($result);
            $styles = $resxml->getElementsByTagName('OutputStyle');
            //get id of the last style returned
            if ($styles->length > 0) {
                $returnedstyle = $styles->item($styles->length-1)->getAttribute('id');
            } else {
				error_log("Could not extract style from RefWorks API response");
                return false;
            }
        }

        $params = array('pgnum'=>1,'pgsize'=>999,'style'=>$returnedstyle,'biblist'=>'false','sort'=>3,'search'=>$folder);

        $result = parent::call_api('retrieve','folder',$params);

        if (!$result) {
			error_log("Could not retrieve folder via RefWorks API");
            return false;
        }
        //parent::call_api('authentication','delsess');

        //get array of titles from $result
        $resxml=new domDocument();
        $resxml->loadXML($result);
        //get xml element that contains the references
        $referencesstring=$resxml->getElementsByTagName('RetrieveResult')->item(0)->nodeValue;


        //strip any weird formatting applied by Refworks
        //(do this over whole string as quiker than per ref)
        if ($replacetags) {
			$referencesstring=str_ireplace('<B></B>','',$referencesstring);
            $referencesstring=str_ireplace('<B>','<span style=\'font-weight:bold\'>',$referencesstring);
            $referencesstring=str_ireplace('</B>','</span>',$referencesstring);
			$referencesstring=str_ireplace('<I></I>','',$referencesstring);
            $referencesstring=str_ireplace('<I>','<span style=\'font-style:italic\'>',$referencesstring);
            $referencesstring=str_ireplace('</I>','</span>',$referencesstring);
			$referencesstring=str_ireplace('<U></U>','',$referencesstring);
            $referencesstring=str_ireplace('<U>','<span style=\'text-decoration:underline\'>',$referencesstring);
            $referencesstring=str_ireplace('</U>','</span>',$referencesstring);
            $referencesstring=strip_tags($referencesstring,'<span>');
        }
        //decode any html entities as we use utf-8
        $referencesstring = str_replace("&amp;", "&", $referencesstring);
        $referencesstring = html_entity_decode($referencesstring, ENT_QUOTES, 'UTF-8');
        //remove atts from <p>
        $referencesstring = preg_replace("/(<p style) {1,}[^>]+>{1,}/","<p>",$referencesstring);
        //special tidy up for MLA
        if ($style=='MLA 7th Edition') {
            //$referencesstring=str_replace('</I>','</I> ', $referencesstring);
            //$referencesstring=str_replace('</span>','</span > ', $referencesstring);
            $referencesstring=str_ireplace('works cited','', $referencesstring);
        }
        $returnedarray = explode("\n", $referencesstring);
        //remove elements in the array not matching the format of contained references
        $returnarray=array();
        foreach ($returnedarray as $value) {
            if ($value!='' && $value!="<p>") {
                $returnarray[]=$value;
            }
        }

        parent::destroy_session();

        //return array
        return $returnarray;
    }


}


?>