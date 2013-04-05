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
            //$document=DOMDocument::loadXML($xml);

            $document=DOMDocument::loadXML($page);
            $xpath=new DOMXPath($document);
            $queryxml = $xpath->query('//*');

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

                $doitype = $doitag->getAttribute('type');
				// SSU want to preserve Reference Type from 'Create Reference' form, so following block (which overwrites) commented out
				/*
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
                        self::$retrievedarray['rt']='11';

                }
				*/
                //work out titles
                $titletags = $xml->getElementsByTagName('article_title');
                foreach ($titletags as $titletag) {
                    self::$retrievedarray['t1']=$titletag->nodeValue;
                }

                $voltags = $xml->getElementsByTagName('volume_title');

                if (empty(self::$retrievedarray['t1'])) {
                    //Make volume title main title (e.g. books)
                    foreach ($voltags as $titletag) {
                        self::$retrievedarray['t1']=$titletag->nodeValue;
                    }
                } else {
                    //make volume volume
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
                //}
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

            $document=DOMDocument::loadXML($page);
            $xpath=new DOMXPath($document);
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
            default:; 
        }

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

            $document=DOMDocument::loadXML($page);
            $xpath=new DOMXPath($document);
       
            //create an array of values from the returned xml which are to populate the reference form
            //$fieldarray = array();
           
            foreach($primotags as $primotag){
                $tempsurname = array();
                $tempgivenname = array();
                
                $isbntags = $xml->getElementsByTagName('isbn');
                foreach($isbntags as $isbntag){
					self::$retrievedarray['sn']=$isbntag->nodeValue;    
                }
                $titletags = $xml->getElementsByTagName('btitle');
                foreach($titletags as $titletag){
					if ($rtvalue==='4') {
						self::$retrievedarray['t2']=$titletag->nodeValue;    
					} else {
						self::$retrievedarray['t1']=$titletag->nodeValue;    
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
				$surnametags = $xml->getElementsByTagName('aulast');
				foreach($surnametags as $surnametag){
					$tempsurname[]=$surnametag->nodeValue;  
				}
				$givennametags = $xml->getElementsByTagName('aufirst');
				foreach($givennametags as $givennametag){
					$tempgivenname[]=$givennametag->nodeValue;
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
					if ($deliverytag->nodeValue === 'Online Resource') {
						$primo_linkback = references_lib::get_setting('primoolinkback'); 
						break;
					} else {
						$primo_linkback = references_lib::get_setting('primoplinkback'); 
					}
				}
				$locationtags = $xml->getElementsByTagName('lds07');
				foreach($locationtags as $locationtag) {
					if ($primo_linkback === references_lib::get_setting('primoplinkback')) {
						// Primo locations use a code for each library rather than name
						// Decoding is site specific
						// Replace lines 500 and 501 with your own sub-library codes and names
						// LJS 12.03.2013
						$librarycodes = array('/; [SLCODE1]/','/; [SLCODE2]/');
		    	 		$librarynames = array('; [Sub-library name 1]','; [Sub-library name 2]');
						$location = $locationtag->nodeValue;
		                $location = preg_replace($librarycodes,$librarynames,$location);		                
						self::$retrievedarray['no'] = 'Location: '.$location;
					} elseif ($primo_linkback === references_lib::get_setting('primoolinkback')) {
						self::$retrievedarray['no'] = 'Ebook';
					}
				}
				$idtags = $xml->getElementsByTagName('recordid');
				foreach($idtags as $idtag){
					// Build link back to Primo
					$linkback = $primobase.$primo_linkback.$idtag->nodeValue;
					self::$retrievedarray['u5']=$linkback;
				}
				self::$retrievedarray['a1']='';
				for($i=0;$i<count($tempsurname);$i++){
					if($i>0){
						self::$retrievedarray['a1'].=' ';   
					}
					self::$retrievedarray['a1'].= trim($tempsurname[$i]);      
					if($tempgivenname[$i]!=null && $tempgivenname[$i]!=''){
						$tempgivenname[$i] = ', '.trim($tempgivenname[$i]);
						self::$retrievedarray['a1'].= $tempgivenname[$i];
						self::$retrievedarray['a1'].=';';       
					}   
				} 
			}
			return true;
		}else{
			return false;    
		}
	}
}
?>