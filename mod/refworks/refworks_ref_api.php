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

class refworks_ref_api extends rwapi{

    public static function get_reference_styles() {
        if (!empty(refworks_base::$config->refworks_styles)) {
            return unserialize(refworks_base::$config->refworks_styles);
        } else {
            return self::$referencestyles;
        }
    }

    //Array of supported reference style names
    //string - string used in get_string
    //quikbib - The name it is known as in RefWorks
    //id - the id of the output style (you can get this from preview styles in RefWorks)
    public static $referencestyles=array(
    array('string'=>'ouharvard','quikbib'=>'Open University (Harvard)','id'=>0),
    array('string'=>'ouharvardhsc','quikbib'=>'Open University (Faculty of Health & Social Care)','id'=>0),
    array('string'=>'mhra','quikbib'=>'MHRA-Modern Humanities Research Association (Notes & Bibliography)','id'=>315),
    array('string'=>'mla','quikbib'=>'MLA 7th Edition','id'=>1571)
    );

    /**
     * Deletes an attachment from a reference
     * @param $rid int: reference id
     * @param $name string: name of file to delete
     * @return success bool
     */
    public static function delete_attachment($rid, $name) {
        return parent::call_api('attachments','refattachdelete',array('rn'=>$rid,'filename'=>$name));
    }

    /**
     * Adds an attachment by uploading in the post stream via the api
     * @param $rid int: reference id
     * @param $attachment string: attachment contents as string
     * @param $name string: name of file
     * @return success bool
     */
    public static function add_attachment($rid,$attachment,$name) {
        return parent::call_api('attachments','refattachadd',array('rn'=>$rid,'filename'=>$name),$attachment);
    }

    /**
     * Initiates call to get a reference attachment - api writes to http stream, this returns file contents
     * @param $rid int: reference id
     * @param $attachment string:filename
     * @return file contents or false if failure
     */
    public static function get_attachment($rid,$attachment) {
        return parent::call_api('attachments','refattach',array('rn'=>$rid,'filename'=>$attachment.''),'');
    }

    /**
     * Deletes reference
     * @param $rid int:id to delete
     * @return success
     */
    public static function delete_ref($rid) {
        return parent::call_api('reference','delete',array('id'=>$rid));
    }


    /**
     * Returns result of an api manuscript::xml call (citations + bibliography)
     * @param $idarray array:array of ids required in bibliography
     * @param $stylename string: name of the style (must be in $referencestyles)
     * @return $result
     */
    public static function get_citations($idarray, $stylename) {
        //using the stylename get the id from $referencestyles
        $returnedstyle = 0;
        $referencestyles = self::get_reference_styles();
        for ($a=0,$max=count($referencestyles);$a<$max;$a++) {
            if ($referencestyles[$a]['string']==$stylename) {
                $returnedstyle = $referencestyles[$a]['id'];
            }
        }
        //if id is 0 - search for name with api
        if ($returnedstyle == 0) {
            $result = parent::call_api('outputstyle','search',array('search'=>$stylename));
            if (!$result) {
                return false;
            }
            $resxml=new domDocument();
            $resxml->loadXML($result);
            $styles = $resxml->getElementsByTagName('OutputStyle');
            if ($styles->length==0) {
                refworks_base::write_error(get_string('style_not_found','refworks'));
                exit;
            }
            //get id of the last style returned
            $returnedstyle = $styles->item($styles->length-1)->getAttribute('id');
        }

        //prepare the xml to send to refworks api
        $citationsxml = '<RWManuscript OutputStyleID="';
        $citationsxml.= $returnedstyle;
        $citationsxml.= '"><Citations location="body">';
        foreach ($idarray as $bits) {
            if ($bits!='' && $bits!= 0) {
                $citationsxml.= '<Cit><Ids>{{'.$bits.'}}</Ids><Formated/></Cit>';
            }
        }
        $citationsxml.= '</Citations><FormattedBib/></RWManuscript>';

        $result = parent::call_api('manuscript','xml','',$citationsxml);
        if ($result == false) {
            return false;
        }
        return $result;
    }

    /**
     * Updates a reference based in the xml string sent
     * @param $refxml string: ref xml (<reference>tags</reference>) must iclude id tag
     * @return success
     */
    public static function update_ref($refxml) {
        $xml='<RWRequest class="reference" method="edit"><RWRefData><refworks>';
        $xml .= $refxml;
        $xml .= '</refworks></RWRefData></RWRequest>';
        return parent::call_api('reference','edit','',$xml);
    }


    /**
     * Creates a reference based on the xml string sent
     * @param $refxml string: ref xml (<reference>tags</reference>) must iclude id tag
     * @return success
     */
    public static function create_ref($refxml, $optionalfolder = '') {
        $xml='<RWRequest class="reference" method="add"><RWRefData><refworks>';
        $xml .= $refxml;
        $xml .= '</refworks></RWRefData></RWRequest>';
        if ($optionalfolder!='') {
            $params = array('folder'=>$optionalfolder,'returnRefs'=>1);
        } else {
            $params = array('returnRefs'=>1);
        }
        return parent::call_api('reference','add',$params,$xml);
    }

    /**
     * Import references into an account
     * @param $document
     * @param $filterid
     * @param $folder
     * @return Int: change!
     */
    public static function import_refs($document,$filterid,$folder='') {
        $params = array('filter'=>$filterid,'encoding'=>65001);
        if ($folder!='') {
            $params['folder'] = $folder;
        }
        $result = parent::call_api('reference','import',$params,$document);

        if ($result == false) {
            return false;
        }
        return true;
    }



    /**
     * Returns the total number of references in an account
     * @return Int:
     */
    public static function count_all_refs() {
        $result = parent::call_api('retrieve','count');
        if ($result == false) {
            return false;
        }
        $resxml=new domDocument();
        $resxml->loadXML($result);
        $count = (int)$resxml->getElementsByTagName('RetrieveCount')->item(0)->getAttribute('count');
        return $count;
    }

    /**
     * Returns references from account or folder
     * @param $curpage
     * @param $numperpage
     * @param $sorting
     * @return xmlstring (from api result, should include reference tags) or sequence of ids if style=-1
     */
    public static function get_all_refs($curpage, $numperpage, $sorting = 9, $style = -1, $format='html', $folder='') {
        $params = array(
            'pgnum'=>$curpage,
            'pgsize'=>$numperpage,
            'sort'=>$sorting,
            'format'=>$format
        );
        if ($style!=0) {
            $params['style'] = $style;
        }
        if ($folder!='') {
            //get all refs from folder
            $params['search'] = $folder;
            $result = parent::call_api('retrieve', 'folder', $params);
        } else {
            //get ids of specified page
            $result = parent::call_api('retrieve', 'all', $params);
        }

        if ($result == false) {
            return false;
        }
        if ($style != -1) {
            return $result;
        }
        //If style is -1, expected list of ids, convert to sequence string
        $resxml=new domDocument();
        $resxml->loadXML($result);
        $idlist = $resxml->getElementsByTagName('id');
        $idstring = '';
        for ($a=0, $max=$idlist->length; $a<$max; $a++) {
            //cannot have array with mutiple elements with same name, so make up a string to send to call_api
            $idstring .= '&id='.$idlist->item($a)->nodeValue;
        }
        return $idstring;
    }

    /**
     * Returns api call to get all references based on id string sent
     * @param $idstring string: id=?&id=? etc
     * @return result string from api
     */
    public static function get_ref_data($idstring) {
        if (strpos($idstring,'id=') == strrpos($idstring,'id=')) {
            //only one ref, use get api call
            $result = parent::call_api('reference', 'get'.$idstring);
        } else {
            //call refworks api - reference get (with multiple ids)
            $idxml = '<RWRequest class="retrieve" method="byid">';
            $idxml .= substr(str_replace('&id=','</id><id>', $idstring),5);
            $idxml .= '</id>';
            $idxml .= '</RWRequest>';
            $result = parent::call_api('retrieve','byid',array('pgnum'=>1,'pgsize'=>9999),$idxml);
        }
        if ($result == false) {
            return false;
        }
        //double check contains reference tags
        if (!strpos($result, '<reference')) {
            return false;
        }
        //return the xml from api call - this can be processed by getting reference tags
        return $result;
    }

    /**
     * Gets all ref ids from any folders
     * Then merges this with standard id list (removing duplicates)
     * @param $idlist string: sequence of ids ?,?,? etc
     * @param $folderlist string: sequence of folder names ?,?,? etc
     * @return array
     */
    public static function merge_id_list($idlist, $folderlist) {
        //first check if any folders selected - get id's of all their refs from refworks
        $folderids = array();

        if ($idlist!='') {
            $idlist = explode(',', $idlist);
        } else {
            $idlist = array();
        }

        if ($folderlist!='') {
            $folderlist = explode('@@', $folderlist);
        } else {
            $folderlist = array();
        }

        for ($a=0, $max=count($folderlist); $a<$max; $a++) {
            //get all the ids of refs from this folder (removing fl_ from name)
            $fldname = str_replace('~~', ' ', substr($folderlist[$a],3));
            $thisfolderids = self::get_all_refs(1, 9999, 9 , -1, 'html' , $fldname);
            if ($thisfolderids!==false && $thisfolderids!='') {
                $thisfolderids = explode('&id=',$thisfolderids);
                //remove duplicates
                for ($b=0, $max2=count($idlist); $b<$max2; $b++) {
                    $key = array_search( $idlist[$b], $thisfolderids ); // Find key of given value
                    if ($key != NULL || $key !== FALSE) {
                        unset($thisfolderids[$key]); // remove key from array
                    }
                }
                $folderids = array_merge($folderids, $thisfolderids);
            }
        }
        //compile lists together
        return array_merge($idlist, $folderids);
    }

    /**
     * Update login of a user account
     * @param $newlogin string:login id
     * @return $result
     */
    public static function update_account_login($newlogin) {

        $login = strtolower(str_replace(' ','_',$newlogin));
        if (!$preresult = self::call_api('user','getprofile','','')) {
            return false;
        }
        $firstposition = stripos($preresult,'<RWProfile');
        $amended = substr($preresult,$firstposition);
        $stringlength = strlen($amended);
        $lastposition = stripos($amended,'</RWProfile>');
        $amendedxml = substr($amended,0,$lastposition+12);
        $userloginstart = stripos($amendedxml,'<userLogin>');
        $userloginfinish = stripos($amendedxml,'</userLogin>');
        $lengthofoldlogin = ($userloginfinish - $userloginstart - 11);
        //convert special characters if any in login
        $convertedlogin = htmlspecialchars($login,ENT_COMPAT,'utf-8',false);
        $finalxml = substr_replace($amendedxml,$convertedlogin,$userloginstart+11,$lengthofoldlogin);
        $userxml = '<RWRequest class="user" method="saveprofile">'.$finalxml.'</RWRequest>';
        $result = self::call_api('user','saveprofile','',$userxml);

        return $result;
    }
}

?>