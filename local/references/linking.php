<?php
/**
 * linking class for creation of a reference (web)links
 * @copyright &copy; 2009 The Open University
 * @author jas2328@openmail.open.ac.uk
 * @author Zotero: in part based on COinS OpenURL function from http://www.zotero.org
 */
require_once(dirname(__FILE__).'/references_lib.php');

class linking{

    public static $inst;
    public static $openurlprefix; //set in config setting openurl
    public static $openurlsuffix = "&amp;sfx.response_type=directlink";

    private static $coursename = '';

    /**
     * creates an array of url via openurl/sfx for references
     * returns null value elements if no link is constructed due to insufficient data
     * @param $document xml dom
     * @return array
     */
    public static function create_link($document, $coursename = '') {
        self::$coursename = $coursename;
        $url =  null;
        $linksarray = array();
        $reflist = $document->getElementsByTagName('reference');
        for ($a = 0,$max = $reflist->length;$a<$max;$a++) {
            $rtnodes = $reflist->item($a)->getElementsByTagName('rt');
            if ($rtnodes->length==1) {
                $rtnodeval = $rtnodes->item(0)->nodeValue;
                //check type of reference and if within designated types proceed else return null
                switch($rtnodeval) {
                    case  'Abstract':
                    case  'Audiovisual Material':
                    case  'Artwork':
                    case  'Bills/Resolutions':
                    case  'Book, Whole':
                    case  'Case':
                    case  'Book Chapter':
                    case  'Book, Edited':
                    case  'Book, Section':
                    case  'Case/Court Decisions';
                    case  'Computer Program':
                    case  'Conference Proceedings':
                    case  'Book Whole':
                    case  'Data File':
                    case  'Dissertation/Thesis':
                    case  'Dissertation/Thesis Unpublished':
                    case  'Web Page':
                    case  'Generic':
                    case  'Hearing':
                    case  'Internet Communication':
                    case  'In Press':
                    case  'Journal':
                    case  'Journal Article':
                    case  'Journal, Electronic':
                    case  'Map':
                    case  'Magazine Article':
                    case  'Motion Picture':
                    case  'Music Score':
                    case  'Newspaper Article':
                    case  'Online Discussion Forum/Blogs':
                    case  'Pamphlet':
                    case  'Patent':
                    case  'Personal Communication':
                    case  'Report':
                    case  'Serial (Book, Monograph)':
                    case  'Slide':
                    case  'Sound Recording':
                    case  'Laws/Statutes':
                    case  'Miscellaneous':
                    case  'Thesis':
                    case  'Unenacted Bill/Resolution':
                    case  'Unpublished Material':
                    case  'Manuscript':
                    case  'Video/DVD' :
                        $url = self::set_type($reflist,$a,$document);
                        break;
                    default:
                        $url = null;
						error_log('No Reference Type found, set URL to null');
                }
            }
            if ($url == false) {
				$url = null;
				error_log('No URL found. Set URL to null');
			}
            $linksarray[] = $url;
        }
        return $linksarray;
    }
    private static function set_type($reflist,$a,$document) {
        //loop thru all child nodes
        if (isset($doc)) {
            array_splice($doc);
        }
        $doc = array();
        if (isset($people)) {
            array_splice($people);
        }
        $people = array();
        for ($b = 0,$maxb = $reflist->item($a)->childNodes->length;$b<$maxb;$b++) {
            $nodenom=$reflist->item($a)->childNodes->item($b)->nodeName;
            $nodeval=$reflist->item($a)->childNodes->item($b)->nodeValue;
            //assign DocType according to format

            // check for certain individual fields/tags and populate $doc and $people arrays accordingly
            switch($nodenom) {
                case 'rt':
                    switch($nodeval) {
                        case 'Journal Article':
                        case 'Journal':
                        case 'Journal, Electronic':
                            $DocType=0;
                            break;
                        case  'Book Chapter':
                        case  'Book, Section':
                        case  'Book, Edited':
                            $DocType=1;
                            break;
                        case 'Book, Whole':
                        case 'Book Whole':
                            $DocType=3;
                            break;
                        case 'Generic':
                            $DocType=6;
                            break;
                        default:
                            $DocType=7;
                            break;
                    }
                    break;
                        case 'vo':
                            $doc["Volume"] = $nodeval;
                            break;
                        case 't1':
                            $doc["DocTitle"] = $nodeval;
                            break;
                        case 'jf':
                            $doc["JournalTitle"] = $nodeval;
                            break;
                        case 'is':
                            $doc["JournalIssue"] = $nodeval;
                            break;
                        case 'sp':
                            $doc["StartPage"] = $nodeval;
                            break;
                        case 'op':
                            $doc["EndPage"] = $nodeval;
                            break;
                        case 'yr':
                            $doc["DocYear"] = $nodeval;
                            break;
                        case 'a1':
                            $people[] = $nodeval;
                            break;
                        case 'lk':
                            //Possiblity of multiple links with ;
                            if (strpos($nodeval,';')) {
                                $nodeval = substr($nodeval,0,strpos($nodeval,';'));
                            }
                            $doc["Link"] = $nodeval;
                            break;
                        case 'u5': //user field in Refworks
                            $doc["Override"] = trim($nodeval);
                            break;
                        case 'no': //might contain doi
                            $doc["Notes"] = $nodeval;
                            break;
                        case 'sn': //ISSN/ISBN
                            $doc['ISSN'] = $nodeval;
                            break;
                        case 'pp'://publisher
                            $doc['BookPublisher'] = $nodeval;
                            break;
                        case 'pb'://place of pub
                            $doc['PubPlace'] = $nodeval;
                            break;
                        default:
                            $doc[$nodenom] = $nodeval;
                            break;
            }
        }
        $returned_url = (self::get_openurl($doc,$DocType,$people));
        return $returned_url;
    }
    /**
     * constructs a url based using a COinS (Zotero) type method
     * returns false if no link is constructed
     * @param $doc array
     * @param $DocType string
     * @param $people array
     * @return array
     */
    private static function get_openurl($doc, $DocType, $people) {
        global $COURSE;
        //check if u5 field/tag has been used for a weblink override and if so use address to instead of openurl
        if (isset($doc["Override"])) {
            if ($doc["Override"] != '' && strtolower($doc["Override"]) != 'none') {
                if (preg_match("|^http://smile.solent.ac.uk/digidocs/live(/.*)?$|i",$doc["Override"])) {
                    return array("url"=>$doc["Override"],"type"=>"digidoc");
                }
                else if (preg_match("|^http(s)??://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i",$doc["Override"])) {
                    return array("url"=>$doc["Override"],"type"=>"override");
                }
            }else if (strtolower($doc["Override"]) == 'none') {
                return false;
            }
        }
        /*if ($DocType > 2 && $DocType != 6 && $DocType != 7) {
         return false;
         }*/
        //echo( '3 stop');
        // Base of the OpenURL specifying which version of the standard we're using.
        $URL = "ctx_ver=Z39.88-2004";
        $URL.= "&amp;url_ver=Z39.88-2004";
        $URL.= "&amp;ctx_enc=info%3Aofi%2Fenc%3AUTF-8";
        //echo(' 4 stop');

        // Metadata format - e.g. article or book.
        if ($DocType == 0) { $URL .= "&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal"; }
        if ($DocType == 1 || $DocType == 3) { $URL .= "&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook"; }

        // An ID for your application.
        //try and get the current course shortname or use the override value
        $idname = 'telstar';
        if (self::$coursename != '') {
            $idname = self::$coursename;
        } else {
            if (isset($COURSE->id) && $COURSE->id != SITEID) {
                if (isset($COURSE->shortname)) {
                    $idname = $COURSE->shortname;
                }
            }
        }
        // Create SID based on host name of Moodle install
        // Create SID based on host name of Moodle install
        global $CFG;
        $sid = parse_url($CFG->wwwroot, PHP_URL_HOST);
        $URL .= "&amp;rfr_id=info:sid/$sid%3A$idname";

        // Document Genre
        if ($DocType == 0) { $URL .= "&amp;rft.genre=article"; }
        if ($DocType == 1) { $URL .= "&amp;rft.genre=bookitem"; }
        if ($DocType == 3) { $URL .= "&amp;rft.genre=book"; }
        if ($DocType == 6) { $URL .= "&amp;rft.genre=unknown"; }

        // Titles
        if ($DocType == 0) {
            if (isset($doc["JournalTitle"])) {
                $URL .= "&amp;rft.jtitle=".urlencode($doc["JournalTitle"]);
            }
            if (isset($doc["DocTitle"])) {
                $URL .= "&amp;rft.atitle=".urlencode($doc["DocTitle"]);
            }
        }else if ($DocType == 1) {
            if (isset($doc["DocTitle"]) && isset($doc["t2"])) {
                $URL .= "&amp;rft.atitle=".urlencode($doc["DocTitle"]);
                $URL .= "&amp;rft.btitle=".urlencode($doc["t2"]);
            }else if (isset($doc["DocTitle"])) {
                $URL .= "&amp;rft.btitle=".urlencode($doc["DocTitle"]);
            }
        } else {
            if (isset($doc["DocTitle"])) {
                $URL .= "&amp;rft.title=".urlencode($doc["DocTitle"]);
            }
        }


        // Volume, Issue, Season, Quarter, and ISSN (for journals)
        if ($DocType == 0) {
            if (isset($doc["Volume"])) { $URL .= "&amp;rft.volume=".urlencode($doc["Volume"]); }
            if (isset($doc["JournalIssue"])) { $URL .= "&amp;rft.issue=".urlencode($doc["JournalIssue"]); }
            if (isset($doc["JournalSeason"])) { $URL .= "&amp;rft.ssn=".urlencode($doc["JournalSeason"]); }
            if (isset($doc["JournalQuarter"])) { $URL .= "&amp;rft.quarter=".urlencode($doc["JournalQuarter"]); }
            if (isset($doc["ISSN"])) { $URL .= "&amp;rft.issn=".urlencode($doc["ISSN"]); }
        }

        // Publisher, Publication Place, and ISBN (for books)
        if ($DocType > 0) {
            if (isset($doc["BookPublisher"])) { $URL .= "&amp;rft.pub=".urlencode($doc["BookPublisher"]);}
            if (isset($doc["PubPlace"])) { $URL .= "&amp;rft.place=".urlencode($doc["PubPlace"]);}
            if (isset($doc["ISSN"])) {
                if ($DocType != 6) {
                    $URL .= "&amp;rft.isbn=".urlencode($doc["ISSN"]);
                } else {
                    //For generic type use issn instead
                    $URL .= "&amp;rft.issn=".urlencode($doc["ISSN"]);
                }
            }
        }

        // Start page and end page (for journals and book articles)
        if ($DocType < 2) {
            if (isset($doc["StartPage"])) {$URL .= "&amp;rft.spage=".urlencode($doc["StartPage"]);}
            if (isset($doc["EndPage"])) {$URL .= "&amp;rft.epage=".urlencode($doc["EndPage"]);}
        }

        //For some types (Web Page) Ref xml has link stored in ed not lk (does not overide an existing link)
        if ($DocType == 7 && !isset($doc["Link"]) && isset($doc['ed'])) {
            if (strpos($doc['ed'],'://') && strpos($doc['ed'],'http')!==false) {
                $doc['Link'] = $doc['ed'];
            }
        }

        // URL ('Generic'  other remaining types)
        // It is possible for this value to contain multiple addresses using a semi-colon as a separator
        if ($DocType > 0) {
            if (isset($doc["Link"])) {
                $URL .= "&amp;rft_id=".urlencode($doc["Link"]);
            }else if (isset($doc['ul'])) {
                $URL .= "&amp;rft_id=".urlencode($doc['ul']);
            }
        }
        //check if there is a DOI in the note field
        $pattern = '/(doi|DOI):+(.*);*/';
        $matching = 0;
        if (isset($doc['do'])) {
            if (stripos($doc['do'],'doi')===0) {
                $doc['do'] = substr($doc['do'],4);
            }
            $URL .= "&amp;rft_id=info:doi/".urlencode($doc['do']);
            $matching = 1;//set matching so later code won't fail
        }else if (isset($doc["Notes"]) && stripos($doc['Notes'],'doi')!==false) {
            $matching = preg_match($pattern,$doc["Notes"],$matches);
            if ($matching) {
                $URL .= "&amp;rft_id=info:doi/".urlencode(substr($matches[0],4));
            }
        }
        // if there is no link in 'remaining type' and no DOI return nothing
        if (($DocType == 7 || $DocType == 3)&& $matching ==0) {
            if (!isset($doc["Link"]) && !isset($doc['ul'])) {
				error_log("Unable to create a URL for linking");
                return false;
            }
        }
        // Publication year.
        if (isset($doc["DocYear"])) {
            $URL .= "&amp;rft.date=".urlencode($doc["DocYear"]);
        }else if ($DocType == 0 || $DocType == 6) {
            //no date on journal - tell sfx to ignore date
            $URL .= "&amp;sfx.ignore_date_threshold=1";
        }

        // Authors
        $i = 0;
        foreach ($people as $individual) {
            $pos = strpos($individual,',');
            if ($pos!== false) {
                $bits = explode(',',$individual,2);
                $URL .= "&amp;rft.au=".urlencode($bits[0]).",".urlencode(substr($bits[1],0));

            }
            $i++;
        }
        if (!isset(self::$openurlprefix)) {
            self::$openurlprefix = references_lib::get_setting('openurl');
        }
        return array("url"=>self::$openurlprefix.$URL.self::$openurlsuffix,"type"=>"z39.88");
    }
}
?>