<?php
/**
 * Main class to connect to an api for retrieval of data
 *
 * @copyright &copy; 2009 The Open University
 * @author j.ackland-snow@open.ac.uk
 * @author j.platts@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local/references/getdata
 */

/** TODO
 * Primo searching needs some refactoring after changes to search for hyphenated and non-hyphenated ISSNs
    ** check_primo_id_status and get_primo_data contain a large chunk of repeated code
    ** this not only increases maintenance overhead but also leads to redundant calls to Primo API
    ** Should refactor to retrieve data from Primo once, and if XML is returned pass to a separate parsing function
*/
require_once(dirname(__FILE__).'/references_lib.php');

class references_getdata{

    public static $retrievedarray = array();
    public static $lasterror = '';

    public static $errorflag;


    /**
     * Main function that calls the crossref api
     * @param $class string:class name you wish to call
     * @param $method string:method to call in class
     * @param $params array:associative array of url params 'param'=>'value' (optional)
     * @param $xml string: xml to post (optional)
     * @return success
     */
    public static function call_crossref_api($doi) {
        global $CFG;
        /////////////////////////////////////////////////////////////////////////////////////
        //$url='http://doi.crossref.org/servlet/query?'; //alternate source (requires different configuration of querystring)
        $url='http://www.crossref.org/openurl/?';
        $username = references_lib::get_setting('crossrefuser');
        $password = references_lib::get_setting('crossrefpwd');
        self::$retrievedarray['do']=$doi;

        if ($password != '') {
            $querystring = 'pid='.$username.':'.$password.'&id=doi:'.$doi.'&noredirect=true';
        } else {
            if ($username == '') {
                //both settings nothing: need some sort of authorisation
                self::$lasterror = 'CrossRef account details not set.';
                return false;
            }
             $querystring = 'pid='.$username.'&id=doi:'.$doi.'&noredirect=true';
        }
        $url.= $querystring;

        $page = download_file_content($url, null, null, true);
        if ($page->status == 200) {
            $page = $page->results;
            if (strpos($page,'<?xml')===false) {
                self::$lasterror = 'Unexpected message format';
                return false;
            }
        } else {
            self::$lasterror = $page->error;
            return false;
        }


        if ($page) {
             //build from xml
            $xml = new DOMDocument('1.0','utf-8');
            $xml->loadXML($page);

             //build from xml
            $doitags = $xml->getElementsByTagName('doi');
            if ($doitags->length==0) {
                return false;
            }

            //check resolved
            $query = $xml->getElementsByTagName('query');
            if ($query->length > 0) {
                if ($query->item(0)->getAttribute('status') == 'unresolved') {
                    return false;
                }
            }

            //create an array of values from the returned xml which are to populate the reference form
            //$fieldarray = array();

            foreach ($doitags as $doitag) {
                $tempsurname = array();
                $tempgivenname = array();

                self::$retrievedarray['do'] = $doitag->nodeValue;
                $doitype = $doitag->getAttribute('type');
                switch($doitype) {
                    case 'book_title':
                        self::$retrievedarray['rt']='5';

                    break;
                    case 'journal_article':
                        self::$retrievedarray['rt']='14';

                    break;
                    case 'conference_paper':
                        self::$retrievedarray['rt']='8';
                    break;
                    default:
                        self::$retrievedarray['rt']='11'; //Defaults to 'Generic'

                }
                //work out titles
                $titletags = $xml->getElementsByTagName('article_title');
                foreach ($titletags as $titletag) {
                    self::$retrievedarray['t1']=$titletag->nodeValue;
                }

                $voltags = $xml->getElementsByTagName('volume_title');
                if ($doitype=='conference_paper') {
                    // If DOI is for conference proceedings, use volume_title as conference name
                    foreach ($voltags as $titletag) {
                        self::$retrievedarray['t2']=$titletag->nodeValue;
                    }
                } else if (empty(self::$retrievedarray['t1'])) {
                    //Make volume_title main title (e.g. for book)
                    foreach ($voltags as $titletag) {
                            self::$retrievedarray['t1']=$titletag->nodeValue;
                    }
                } else {
                    // make volume volume
                    foreach ($voltags as $titletag) {
                        self::$retrievedarray['vo']=$titletag->nodeValue;
                    }
                }
                //now load array from tags common to 'book_title' and 'journal_article'
                //if ($doitype == 'book_title' || 'journal_article') {
                $isbntags = $xml->getElementsByTagName('isbn');
                foreach ($isbntags as $isbntag) {
                    self::$retrievedarray['sn']=$isbntag->nodeValue;
                }
                $issntags = $xml->getElementsByTagName('issn');
                foreach ($issntags as $issntag) {
                    self::$retrievedarray['sn']=$issntag->nodeValue;
                }
                $journaltags = $xml->getElementsByTagName('journal_title');
                foreach ($journaltags as $journaltag) {
                    self::$retrievedarray['jf']=$journaltag->nodeValue;
                }
                $volumetags = $xml->getElementsByTagName('volume');
                foreach ($volumetags as $volumetag) {
                    self::$retrievedarray['vo']=$volumetag->nodeValue;
                }
                $issuetags = $xml->getElementsByTagName('issue');
                foreach ($issuetags as $issuetag) {
                    self::$retrievedarray['is']=$issuetag->nodeValue;
                }
                $firstptags = $xml->getElementsByTagName('first_page');
                foreach ($firstptags as $firstptag) {
                    self::$retrievedarray['sp']=$firstptag->nodeValue;
                }
                $lastptags = $xml->getElementsByTagName('last_page');
                foreach ($lastptags as $lastptag) {
                    self::$retrievedarray['op']=$lastptag->nodeValue;
                }
                $yeartags = $xml->getElementsByTagName('year');
                foreach ($yeartags as $yeartag) {
                    self::$retrievedarray['yr']=$yeartag->nodeValue;
                }

                $surnametags = $xml->getElementsByTagName('surname');
                foreach ($surnametags as $surnametag) {
                    $tempsurname[]=$surnametag->nodeValue;
                }
                $givennametags = $xml->getElementsByTagName('given_name');
                foreach ($givennametags as $givennametag) {
                    $tempgivenname[]=$givennametag->nodeValue;
                }
                self::$retrievedarray['a1']='';
                for ($i=0;$i<count($tempsurname);$i++) {
                    if ($i>0) {
                        self::$retrievedarray['a1'].=' ';
                    }
                    self::$retrievedarray['a1'].= trim($tempsurname[$i]);
                    if ($tempgivenname[$i]!=null && $tempgivenname[$i]!='') {
                        $tempgivenname[$i] = ', '.str_replace(' ','',trim($tempgivenname[$i]));
                        self::$retrievedarray['a1'].= $tempgivenname[$i];
                        self::$retrievedarray['a1'].=';';
                    }
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Main function that calls the worldcat api
     * @param $identifier string:the identifier of the record requested
     * @param $querytype string:the type of identifier being submitted
     * @return success
     */
    public static function call_worldcat_api($identifier,$querytype = 'isbn') {
        global $CFG;

        //identifier cleaner
        switch($querytype) {
            case 'isbn':
                $patterns = array();
                $replacements = array();
                $patterns[0] = '/\s+/';
                $patterns[1] = '/-/';
                $replacements[1] = '';
                $replacements[0] = '';
                $identifier = trim($identifier);
                $identifier = preg_replace($patterns, $replacements, $identifier);
            break;
            default:;
        }




        $url='http://www.worldcat.org/webservices/catalog/content/';
        $divider = '/';
        $wskey = references_lib::get_setting('wcwskey');
        $querystring = '?&wskey='.$wskey;
        $url.= $querytype.$divider.$identifier.$querystring;

        $page = download_file_content($url, null, null, true);
        if ($page->status == 200) {
            $page = $page->results;
            if (strpos($page,'<?xml')===false) {
                self::$lasterror = 'Unexpected message format';
                return false;
            }
        } else {
            self::$lasterror = $page->error;
            return false;
        }

        if ($page) {
             //build from xml
            $xml = new DOMDocument('1.0','utf-8');
            $xml->loadXML($page);
            $records = $xml->getElementsByTagName('record');
            if ($records->length==0) {
                return false;
            }

            $xpath=new DOMXPath($xml);
            $queryxml = $xpath->query('//*');

            $editors=''; //set variables
            $publisher='';
            $place='';
            $year='';
            $tempsurname = array();
            $tempgivenname = array();
            if ($querytype=='isbn') {
                self::$retrievedarray['rt']='5';
            }

            foreach ($queryxml as $datafieldtag) {
                if ($tag = $datafieldtag->getAttribute('tag')) {
                    switch($tag) {
                        case 100: //author
                            $justname = '';
                            if ($datafieldtag->hasChildNodes()) {
                                $children = $datafieldtag->childNodes;
                                for ($i=0;$i<$children->length;$i++) {
                                    $achild = $children->item($i);
                                    if ($achild->nodeType == 1) {
                                        if ($achild->hasAttributes()) {
                                            $code = $achild->getAttribute('code');
                                            switch($code) {
                                                case 'a': $justname = trim($achild->nodeValue,",");
                                                break;
                                                default: //TODO
                                                ;
                                            }
                                        }
                                    }
                                }
                            }
                            self::$retrievedarray['a1'] = $justname;
                        break;
                        case 245: //title
                            //$child = $title->textContent;
                            $singletitle = $datafieldtag->nodeValue;
                            //echo ' singletitle = '.$singletitle.'<br />';
                            $pos = strripos($singletitle,'/');
                            $editedtitle = trim(substr($singletitle,0,$pos),",");
                            //remove excessive whitespace from the title
                            $editedtitle = preg_replace( '/\s+/', ' ', $editedtitle );
                            self::$retrievedarray['t1'] = trim($editedtitle);
                        break;
                        case 260: //publisher
                            $publishing = $datafieldtag->nodeValue;
                            if ($datafieldtag->hasChildNodes()) {
                                $children = $datafieldtag->childNodes;
                                for ($i=0;$i<$children->length;$i++) {
                                    $achild = $children->item($i);
                                    if ($achild->nodeType == 1) {
                                        if ($achild->hasAttributes()) {
                                            $code = $achild->getAttribute('code');
                                            switch($code) {
                                                case 'a': self::$retrievedarray['pp'] = trim($achild->nodeValue,",");
                                                break;
                                                case 'b': self::$retrievedarray['pb'] = trim($achild->nodeValue,",");
                                                break;
                                                case 'c':
                                                    $pattern = '/([^0-9-\/]+)/';
                                                    $replacement = '';
                                                    $string = $achild->nodeValue;
                                                    // remove non-numeric characters (apart from '/' or '-') from the 'year'
                                                    self::$retrievedarray['yr'] = substr(trim(preg_replace($pattern, $replacement, $string),","),0,4);
                                                break;
                                                default: //TODO
                                                ;
                                            }
                                        }
                                    }
                                }
                            }
                        break;
                        case 700: //editor(s)
                            if ($editors!='') {
                                $editors.= '; ';
                            }
                            if ($datafieldtag->hasChildNodes()) {
                                $children = $datafieldtag->childNodes;
                                for ($i=0;$i<2;$i++) {
                                    //echo 'index: '.$i.' ';
                                    $child = $children->item($i);
                                    if (trim($child->nodeName)!='#text') {
                                        //echo 'child node name = '.$child->nodeName;
                                        //echo ' child node value = '.$child->nodeValue.'<br />';
                                        $editors.= trim($child->nodeValue,",");
                                    }
                                }
                            }
                        break;
                        default: //TODO
                            ;
                    }
                }
            }
            self::$retrievedarray['a2'] = $editors;
            return true;
        } else {
            return false;
        }
    }

	/**
     * Main function that calls the primo api
     * The content is based on the Ex Libris API but is not endorsed or certified by Ex Libris.
     * @param $identifier string:the identifier of the record requested
     * @param $querytype string:the type of identifier being submitted
     * @return success
     */
	public static function call_primo_api($identifier,$querytype = 'isbn', $rtvalue){
	    global $CFG;
        $identifiers = array();
	        //identifier cleaner
	    switch($querytype){
            case 'isbn':
                $patterns = array();
                $replacements = array();
                $patterns[0] = '/\s+/';
                $patterns[1] = '/-/';
                $replacements[1] = '';
                $replacements[0] = '';
                $identifier = trim($identifier);
                $identifier = preg_replace($patterns, $replacements, $identifier);
				$index = 'isbn';
            break;
            case 'issn':
                $patterns = array();
                $replacements = array();
                $patterns[0] = '/\s+/';
                $patterns[1] = '/-/';
                $replacements[1] = '';
                $replacements[0] = '';
                $identifier = trim($identifier);
                $identifier = preg_replace($patterns, $replacements, $identifier);
                $identifiers[0] = $identifier;
                $identifiers[1] = substr($identifier,0,4)."-".substr($identifier,4);
                $index = 'issn';
            break;
            case 'recordid':
                $index = 'rid';
            break;
            default:; 
        }
        if (count($identifiers)>0) {
            $id_statuses = array();
            foreach($identifiers as $id) {
                $id_status = self::check_primo_id_status($id,$index);
                if($id_status) {
                    $id_statuses[$id_status] = $id;
                }
            }
            if(array_key_exists("online", $id_statuses)){
                $identifier = $id_statuses["online"];
            } else if (array_key_exists("print",$id_statuses)) {
                $identifier = $id_statuses["print"];
            } else {
                return false;
            }
        }
        //Create array of identifiers
        //For each identifier:
        //Check to see if online
        //For first one that's online retrieve the reference
        //Else fall back to first non-online reference
        if (self::get_primo_data($identifier,$index,$rtvalue)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if ID gets a hit on Primo and whether online or print
     * The content is based on the Ex Libris API but is not endorsed or certified by Ex Libris.
     * @param $id string:the identifier of the record requested
     * @param $index string:the primo index to search
     * @return format (online or print)
     */
    public static function check_primo_id_status($identifier,$index) {

        // Separate various URL parameters for ease
        // Some set from MyReferences configuration as site specific
        $primobase = references_lib::get_setting('primourl'); 
        $primoport = references_lib::get_setting('primoport');
        $path = '/PrimoWebServices/xservice/search/brief?';
        $primoinstitution = references_lib::get_setting('primoinst');
        $oncampus = 'onCampus=true';
        $query = 'query='.$index.',contains,'.$identifier;
        $index_position = 'indx=1';
        $bulk = 'bulkSize=1';
        
        if($primobase != '') {
            $url = $primobase;
        } else {
            self::$lasterror = 'Primo Base URL not set';
            return false;
        }
        if($primoport != '') {
            $url .= ':'.$primoport;
        }
        if($primoinstitution != '') {
            $institution = 'institution='.$primoinstitution;
        } else {
            self::$lasterror = 'Primo Institution not set';
            return false;
        }
        
        // Build full query string and url from parameters
        $querystring = $institution.'&'.$oncampus.'&'.$query.'&'.$index_position.'&'.$bulk;
        $url.= $path.$querystring;
        error_log($url);
        
        $page = download_file_content($url, null, null, true);
        if ($page->status == 200) {
            $page = $page->results;
            if (strpos($page,'<sear')===false) {
                self::$lasterror = 'Unexpected message format';
                return false;
            }
        } else {
            self::$lasterror = $page->error;
            return false;
        }
                
        if($page){
             //build from xml
            $xml = new DOMDocument('1.0','utf-8');
            $xml->loadXML($page);
    
            $primotags = $xml->getElementsByTagNameNS('http://www.exlibrisgroup.com/xsd/jaguar/search','DOC');
            if($primotags->length==0){
                return false;
            }
       
            //create an array of values from the returned xml which are to populate the reference form
            //$fieldarray = array();
           
            foreach($primotags as $primotag){
                $deliverytags = $xml->getElementsByTagName('delcategory');
                foreach($deliverytags as $deliverytag) {
                    // Looking for "Physical Item", "Online Resource", "SFX Resource"
                    switch($deliverytag->nodeValue) {
                        case 'Physical Item': 
                            $primo_linkback = references_lib::get_setting('primoplinkback');
                            $access_online = FALSE;
                            break;
                        case 'Online Resource':
                            $primo_linkback = references_lib::get_setting('primoolinkback'); 
                            $access_online = TRUE;
                            break;
                        case 'SFX Resource':
                            $primo_linkback = references_lib::get_setting('primoolinkback'); 
                            $access_online = TRUE;
                            break;
                        default:
                            $access_online = FALSE;
                    }
                    // If we've got online access, stop checking
                    if ($access_online) {
                        break;
                    }
                }
                // Location can be in lds07 or lds04 depending on material type
                // test $material_type - can be BOOK, JOUR, VIDEO
                // Combine $material_type and $online_access to decide on ['av'], ['no'] and location info
            }
            if($access_online) {
                return "online";
            } else {
                return "print";
            }
        } else {
            return false;
        }
    }

    /**
     * Retrieves data from Primo
     * The content is based on the Ex Libris API but is not endorsed or certified by Ex Libris.
     * @param $identifier string:the identifier of the record requested
     * @param $index string:the primo index to search
     * @param $rtvalue string: the Reference Type value from the reference edit form if it has been passed
     * @return success
     */
    public static function get_primo_data($identifier,$index,$rtvalue) {

		// Separate various URL parameters for ease
		// Some set from MyReferences configuration as site specific
        $primobase = references_lib::get_setting('primourl'); 
		$primoport = references_lib::get_setting('primoport');
		$path = '/PrimoWebServices/xservice/search/brief?';
		$primoinstitution = references_lib::get_setting('primoinst');
		$oncampus = 'onCampus=true';
		$query = 'query='.$index.',contains,'.$identifier;
		$index_position = 'indx=1';
		$bulk = 'bulkSize=1';
		
		if($primobase != '') {
			$url = $primobase;
		} else {
			self::$lasterror = 'Primo Base URL not set';
			return false;
		}
		if($primoport != '') {
			$url .= ':'.$primoport;
		}
		if($primoinstitution != '') {
			$institution = 'institution='.$primoinstitution;
		} else {
			self::$lasterror = 'Primo Institution not set';
			return false;
		}
		
		// Build full query string and url from parameters
        $querystring = $institution.'&'.$oncampus.'&'.$query.'&'.$index_position.'&'.$bulk;
        $url.= $path.$querystring;
        error_log($url);
		
		$page = download_file_content($url, null, null, true);
		if ($page->status == 200) {
			$page = $page->results;
			if (strpos($page,'<sear')===false) {
				self::$lasterror = 'Unexpected message format';
				return false;
			}
		} else {
			self::$lasterror = $page->error;
			return false;
		}
				
		if($page){
             //build from xml
            $xml = new DOMDocument('1.0','utf-8');
            $xml->loadXML($page);
    
            $primotags = $xml->getElementsByTagNameNS('http://www.exlibrisgroup.com/xsd/jaguar/search','DOC');
            if($primotags->length==0){
                return false;
            }
       
            //create an array of values from the returned xml which are to populate the reference form
            //$fieldarray = array();
           
            foreach($primotags as $primotag){
                $tempcontribs = array();
                $ristypetags = $xml->getElementsByTagName('ristype');
                foreach($ristypetags as $ristypetag) {
                    $ristype = $ristypetag->nodeValue;
                    switch($ristype) {
                        case 'BOOK':
                            self::$retrievedarray['rt']='5';
                            $material_type = "BOOK";
                            break;
                        case 'JOUR':
                            self::$retrievedarray['rt']='11';
                            $material_type = "JOUR";
                            break;
                        case 'VIDEO':
                            self::$retrievedarray['rt']='29';
                            $material_type = "VIDEO";
                            $risdatetags = $xml->getElementsByTagName('risdate');
                            foreach($risdatetags as $risdatetag) {
                                self::$retrievedarray['fd'] = trim($risdatetag->nodeValue,".");
                            }
                            break;
                        default:
                            self::$retrievedarray['rt']='11'; //Defaults to 'Generic'
                    }
                }
                
                $isbntags = $xml->getElementsByTagName('isbn');
                foreach($isbntags as $isbntag){
					self::$retrievedarray['sn']=$isbntag->nodeValue;    
                }
                if(!array_key_exists('sn',self::$retrievedarray)) {
                    $issntags = $xml->getElementsByTagName('issn');
                    foreach($issntags as $issntag){
                        self::$retrievedarray['sn']=$issntag->nodeValue;    
                    }
                }
                $titletags = $xml->getElementsByTagName('btitle');
                foreach($titletags as $titletag){
					if ($rtvalue==='4') {
						self::$retrievedarray['t2']=$titletag->nodeValue;    
					} else {
						self::$retrievedarray['t1']=$titletag->nodeValue;    
					}
                }
                $jtitletags = $xml->getElementsByTagName('jtitle');
                foreach($jtitletags as $jtitletag) {
                    self::$retrievedarray['jf']=$jtitletag->nodeValue;
                }
                if(array_key_exists('jf', self::$retrievedarray)) {
                    $sjtitletags = $xml->getElementsByTagName('stitle');
                    foreach($sjtitletags as $sjtitletag) {
                        self::$retrievedarray['jo']=$sjtitletag->nodeValue;
                    }
                }
				$yeartags = $xml->getElementsByTagName('date');
				foreach($yeartags as $yeartag){
					self::$retrievedarray['yr']=$yeartag->nodeValue;    
				}
                $placetags = $xml->getElementsByTagName('cop');
				foreach($placetags as $placetag){
					self::$retrievedarray['pp']=$placetag->nodeValue;
				}
				$pubtags = $xml->getElementsByTagName('pub');
				foreach($pubtags as $pubtag){
					self::$retrievedarray['pb']=$pubtag->nodeValue;
				}
                $creatortags = $xml->getElementsByTagName('creator');
                foreach($creatortags as $creatortag){
                    $tempcontribs[] = $creatortag->nodeValue;
                }
                $contributortags = $xml->getElementsByTagName('contributor');
                foreach($contributortags as $contributortag){
                    $tempcontribs[] = $contributortag->nodeValue;
                }
				$editiontags = $xml->getElementsByTagName('edition');
				foreach($editiontags as $editiontag){
					$edition = $editiontag->nodeValue;
					// If it starts with a number (or two numbers separated by / or -) then only use the number
					// otherwise use the whole statement
					if (preg_match('/^\d+\/?-?\d*/',$edition, $matches) > 0) {
						$edition = $matches[0];
					}
					self::$retrievedarray['ed']=$edition;
				}
				$deliverytags = $xml->getElementsByTagName('delcategory');
				foreach($deliverytags as $deliverytag) {
                    // Looking for "Physical Item", "Online Resource", "SFX Resource"
                    switch($deliverytag->nodeValue) {
                        case 'Physical Item': 
                            $primo_linkback = references_lib::get_setting('primoplinkback');
                            $access_online = FALSE;
                            break;
                        case 'Online Resource':
                            $primo_linkback = references_lib::get_setting('primoolinkback'); 
                            $access_online = TRUE;
                            break;
                        case 'SFX Resource':
                            $primo_linkback = references_lib::get_setting('primoolinkback'); 
                            $access_online = TRUE;
                            break;
                        default:
                            $access_online = FALSE;
                    }
                    // If we've got online access, stop checking
					if ($access_online) {
						break;
					}
				}
                // Location can be in lds07 or lds04 depending on material type
                // test $material_type - can be BOOK, JOUR, VIDEO
                // Combine $material_type and $online_access to decide on ['av'], ['no'] and location info
                if ($material_type === "BOOK") {
                    if ($access_online) {
                        self::$retrievedarray['av'] = references_lib::get_setting('bk_online_avail');
                        self::$retrievedarray['no'] = references_lib::get_setting('bk_online_location');
                    } else {
                        self::$retrievedarray['av'] = references_lib::get_setting('bk_print_avail');
                        $locationtags = $xml->getElementsByTagName('lds07');
                        foreach($locationtags as $locationtag) {
                            $location = $locationtag->nodeValue;
                            // Primo locations use a code for each library rather than name
                            // Decoding is site specific
                            // Make setting into JSON (as long as setting formatted correctly)
                            $primolibs_json = "{".references_lib::get_setting('primolibs')."}";
                            $librarynames = json_decode($primolibs_json);
                            switch (json_last_error()) {
                                case JSON_ERROR_NONE:
                                    foreach ($librarynames as $librarycode => $libraryname) {
                                        $location = preg_replace('/; '.$librarycode.'/','; '.$libraryname,$location);
                                    }
                                break;
                                default:
                                    error_log("Please check the 'primolibs' setting is formatted correctly");
                                break;
                            }
                            self::$retrievedarray['no'] = 'Location: '.$location;
                        }
                        if(self::$retrievedarray['no'] === 'Location: ') {
                            self::$retrievedarray['no'] = references_lib::get_setting('bk_print_location');
                        }
                    }
                } elseif ($material_type === "JOUR") {
                    if ($access_online) {
                        self::$retrievedarray['av'] = references_lib::get_setting('jn_online_avail');
                        self::$retrievedarray['no'] = references_lib::get_setting('jn_online_location');
                    } else {
                        self::$retrievedarray['av'] = references_lib::get_setting('jn_print_avail');
                        $locationtags = $xml->getElementsByTagName('lds04');
                        foreach($locationtags as $locationtag) {
                            $location = $locationtag->nodeValue;
                            self::$retrievedarray['no'] = 'Location: '.$location;
                        }
                        if(self::$retrievedarray['no'] === 'Location: ') {
                            self::$retrievedarray['no'] = references_lib::get_setting('bk_print_location');
                        }
                    }
                } elseif ($material_type === "VIDEO") {
                    if ($access_online) {
                        self::$retrievedarray['av'] = references_lib::get_setting('vid_online_avail');
                        self::$retrievedarray['no'] = references_lib::get_setting('vid_online_location');
                    } else {
                        self::$retrievedarray['av'] = references_lib::get_setting('vid_print_avail');
                        $locationtags = $xml->getElementsByTagName('lds07');
                        foreach($locationtags as $locationtag) {
                            $location = $locationtag->nodeValue;
                            // Primo locations use a code for each library rather than name
                            // Decoding is site specific
                            // Make setting into JSON (as long as setting formatted correctly)
                            $primolibs_json = "{".references_lib::get_setting('primolibs')."}";
                            $librarynames = json_decode($primolibs_json);
                            switch (json_last_error()) {
                                case JSON_ERROR_NONE:
                                    foreach ($librarynames as $librarycode => $libraryname) {
                                        $location = preg_replace('/; '.$librarycode.'/','; '.$libraryname,$location);
                                    }
                                break;
                                default:
                                    error_log("Please check the 'primolibs' setting is formatted correctly");
                                break;
                            }
                            self::$retrievedarray['no'] = 'Location: '.$location;
                        }
                        if(self::$retrievedarray['no'] === 'Location: ') {
                            self::$retrievedarray['no'] = references_lib::get_setting('vid_print_location');
                        }
                    }
                }
                $linktags = $xml->getElementsByTagName('linktorsrc');
                foreach($linktags as $linktag) {
                    // TODO Check for  "Click here for more information about"
                    $link = $linktag->nodeValue;
                    if (preg_match('/more information about (.*)$/',$link, $matches) > 0) {
                        $link = $matches[1];
                        self::$retrievedarray['db'] = $link;
                        break;
                    }
                }
				$idtags = $xml->getElementsByTagName('recordid');
				foreach($idtags as $idtag){
					// Build link back to Primo
					$linkback = $primobase.$primo_linkback.$idtag->nodeValue;
					self::$retrievedarray['u5']=$linkback;
				}
				self::$retrievedarray['a1']='';
				for($i=0;$i<count($tempcontribs);$i++){
					if($i>0){
						self::$retrievedarray['a1'].='; ';   
					}
					self::$retrievedarray['a1'].= trim($tempcontribs[$i]);
				} 
			}
			return true;
		}else{
			return false;    
		}
	}
}
?>