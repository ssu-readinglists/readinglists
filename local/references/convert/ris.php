<?php
/**
 * RIS conversion class - tag conversion partially based on RefBase code
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author RefBase: Matthias Steffens <mailto:refbase@extracts.de> and the file's original author(s)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package convert
 */
//include_once 'refbase.php';
class ris extends references_convert_format{
    //definitions of transforms (used for search & replace)
    //based on refbase RIS import - <http://www.refbase.net>
    private $supportedtags = array(
                                            "TY"  =>  "rt", // Type of reference (IMPORTANT: the array element that maps to 'type' must be listed as the first element!)

    //"AU"  =>  "author", // Author Primary
                                            "A1"  =>  "a1", // Author Primary
    //"A2"  =>  "editor", // Author Secondary (see note for 'corporate_author' below)
    //                                    "ED"  =>  "editor", // Author Secondary
    //                                    "A3"  =>  "series_editor", // Author Series
                                            "AD"  =>  "ad", // Address
    //                                    ""    =>  "corporate_author", // note that bibutils uses the RIS 'A2' tag to indicate corporate authors ('<name type="corporate">'), e.g., when importing contents of the BibTeX 'organization' field

    //                                    "TI"  =>  "t1", // Title Primary
                                            "T1"  =>  "t1", // Title Primary
                                            "T2"  =>  "t2", // Title secondary
    //                                    "CT"  =>  "title_other", // Title Primary
    //                                    ""    =>  "orig_title",

                                            "Y1"  =>  "yr", // Date Primary (same syntax rules as for "PY")
                                            "PY"  =>  "yr", // Date Primary (date must be in the following format: "YYYY/MM/DD/other_info"; the year, month and day fields are all numeric; the other info field can be any string of letters, spaces and hyphens; note that each specific date information is optional, however the slashes ("/") are not)
                                            "Y2"  =>  "fd", // Date Secondary (same syntax rules as for "PY")

    //                                    "BT"  =>  array("BOOK" => "series_title", "STD" => "series_title", "THES" => "series_title", "Other" => "publication"), // according to <http://www.refman.com/support/risformat_tags_01.asp> this would be: array("BOOK" => "title", "Other" => "publication"), // Book Whole: Title Primary; Other reference types: Title Secondary
                                            "JF"  =>  "jf", // Periodical name: full format
    //                                    "JO"  =>  "publication", // Periodical name: full format
                                            "JA"  =>  "jo", // Periodical name: standard abbreviation
    //                                    "J1"  =>  "abbrev_journal", // Periodical name: user abbreviation 1
    //                                    "J2"  =>  "abbrev_journal", // Periodical name: user abbreviation 2
    //                                    "T2"  =>  array("JOUR" => "abbrev_journal", "CHAP" => "publication", "Other" => "abbrev_series_title"), // Title Secondary (## "T2" is used by bibutils (instead of "JA") for abbreviated journal names! ##)
    //                                    "T3"  =>  "series_title", // Title Series (in case of "TY=CONF", "T3" appears to be used for conference title)

                                            "VL"  =>  "vo", // Volume number
                                            "IS"  =>  "is", // Issue
                                            "SP"  =>  "sp", // Start page number (contents of the special fields 'startPage' and 'endPage' will be merged into a range and copied to the refbase 'pages' field)
                                            "EP"  =>  "op", // Ending page number
                                            "LP"  =>  "op", // Ending page number ('LP' is actually not part of the RIS specification but gets used in the wild such as in RIS exports of the American Physical Society, <http://aps.org/>)

    //                                    ""    =>  "series_volume", // (for 'series_volume' and 'series_issue', some magic will be applied within the 'parseRecords()' function)
    //                                    ""    =>  "series_issue",

                                            "PB"  =>  "pb", // Publisher
                                            "CY"  =>  "pp", // City of publication
                                            "CP"  =>  "pp", // City of publication

    //                                    ""    =>  "edition",
    //                                    ""    =>  "medium",
                                            "SN"  =>  'sn',

    //                                    ""    =>  "language",
    //                                    ""    =>  "summary_language",

                                            "KW"  =>  "k1", // Keywords
                                            "AB"  =>  "ab", // Abstract
    //                                    "N2"  =>  "abstract", // Abstract

    //                                    ""    =>  "area",
    //                                    ""    =>  "expedition",
    //                                    ""    =>  "conference",

                                        "DO"    =>  "do",    //DOI
                                            "UR"  =>  "lk", // URL (URL addresses can be entered individually, one per tag or multiple addresses can be entered on one line using a semi-colon as a separator)
                                            "UR"  =>  "ul", // URL second field for url - only found in XML formats - hence turned to UR (UR should be turned to ul, so is above this)
    //                                    "L1"  =>  "file", // Link to PDF (same syntax rules as for "UR")
    //                                    "L2"  =>  "", // Link to Full-text (same syntax rules as for "UR")
    //                                    "L3"  =>  "related", // Related Records (this mapping would require some postprocessing of the field value so that it's suitable for the 'related' field)
    //                                    "L4"  =>  "", // Image(s)

                                            "N1"  =>  "no", // Notes
                                            "ID"  =>  "id", // Reference ID (NOTE: if no other field gets mapped to the 'cite_key' field, the contents of the 'call_number' field will be also copied to the 'cite_key' field of the currently logged-in user)

    //                                    "M1"  =>  "", // Miscellaneous 1
    //                                    "M2"  =>  "", // Miscellaneous 2
    //                                    "M3"  =>  "", // Miscellaneous 3
                                            "U1"  =>  "u1", // User definable 1 ('U1' is used by Bibutils to indicate the type of thesis, e.g. "Masters thesis" or "Ph.D. thesis"; function 'parseRecords()' will further tweak the contents of the refbase 'thesis' field)
                                            "U2"  =>  "u2", // User definable 2
                                            "U3"  =>  "u3", // User definable 3
                                            "U4"  =>  "u4", // User definable 4
                                            "U5"  =>  "u5", // User definable 5

    //                                    ""    =>  "contribution_id",
    //                                    ""    =>  "online_publication",
    //                                    ""    =>  "online_citation",
    //                                    ""    =>  "approved",
    //                                    ""    =>  "orig_record",

    //                                    "RP"  =>  "copy", // Reprint status (valid values: "IN FILE", "NOT IN FILE", "ON REQUEST (MM/DD/YY)") (this mapping would require some postprocessing of the field value so that it's suitable for the 'copy' field)
                                            "AV"  =>  "av", // Availability
    );

    private $referencetypes = array( // escaping backslash added to occurences of forward slashes
            "ABST"       =>  "Abstract", // Abstract
            "ADVS"       =>  "Audiovisual Material", // Audiovisual material
            "ART"        =>  "Art Work", // Art Work
            "BILL"       =>  "Bills/Resolutions", // Bill/Resolution
            "BOOK"       =>  "Book, Whole", // Book, Whole
            "CASE"       =>  "Case", // Case
            "CHAP"       =>  "Book, Section", // Book chapter
            "CHAPTER"    =>  "Book Chapter", // Book chapter (the incorrect CHAPTER type gets used by SpringerLink, see e.g. RIS output at <http://www.springerlink.com/content/57w5dd51eh0h8a25>)
            "COMP"       =>  "Computer Program", // Computer program
            "CONF"       =>  "Conference Proceedings", // Conference proceeding
            "CTLG"       =>  "Book Whole", // Catalog (#fallback#)
            "DATA"       =>  "Data File", // Data file
            "ELEC"       =>  "Web Page", // Electronic Citation - WEB PAGE
            "GEN"        =>  "Generic", // Generic
            "HEAR"       =>  "Hearing", // Hearing
            "ICOMM"      =>  "Web Page", // Internet Communication
            "INPR"       =>  "In Press", // In Press
            "JFULL"      =>  "Journal", // Journal (full)
            "JOUR"       =>  "Journal Article", // Journal
            "MAP"        =>  "Map", // Map
            "MGZN"       =>  "Magazine Article", // Magazine article
            "MPCT"       =>  "Motion Picture", // Motion picture
            "MUSIC"      =>  "Music Score", // Music score
            "NEWS"       =>  "Newspaper Article", // Newspaper
            "PAMP"       =>  "Pamphlet", // Pamphlet
            "PAT"        =>  "Patent", // Patent
            "PCOMM"      =>  "Personal Communication", // Personal communication
            "RPRT"       =>  "Report", // Report
            "SER"        =>  "Serial (Book, Monograph)", // Serial (Book, Monograph)
            "SLIDE"      =>  "Slide", // Slide
            "SOUND"      =>  "Sound Recording", // Sound recording
            "STAT"       =>  "Laws/Statutes", // Statute
            "STD"        =>  "Miscellaneous", // Generic (note that 'STD' is used by bibutils although it is NOT listed as a recognized reference type at <http://www.refman.com/support/risformat_reftypes.asp>)
            "THES"       =>  "Thesis", // Thesis/Dissertation (function 'parseRecords()' will set the special type 'Thesis' back to 'Book Whole' and adopt the refbase 'thesis' field)
            "UNBILL"     =>  "Unenacted Bill/Resolution", // Unenacted bill/resolution
            "UNPB"       =>  "Manuscript", // Unpublished work (#fallback#)
            "VIDEO"      =>  "Video/DVD" // Video recording
    );

    public function __construct() {

    }

    public static function can_import() {
        return true;
    }

    public static function can_export() {
        return true;
    }

    /**
     * imports a ris format text file & converts to array format - then to xml?
     * @data ris format text string
     * returns string:
     */
    public function import(&$data) {

        if (mb_detect_encoding($data, "UTF-8, ASCII, ISO-8859-1")!="UTF-8") {
            $data=utf8_encode($data);
        }

        //ris serialise to array (direct 1:1)
        $risarray=$this->ris_to_array(&$data);

        //convert from array to dom (converts types etc)
        $doc = new DOMDocument('1.0','utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $newnode=$doc->createElement('references');
        $doc->appendChild($newnode);
        for ($a=0,$max=count($risarray);$a<$max;$a++) {
            $recnode=$doc->createElement('reference');
            foreach ($risarray[$a] as $item) {
                foreach ($item as $record=>$val) {
                    // (re)set ris format error flag
                    $this->make_node($record,$val,&$recnode,&$doc);
                }
            }
            $newnode->appendChild($recnode);
            //add id attribute
            $recnode->setAttribute('id',$recnode->getElementsByTagName('id')->item(0)->nodeValue);
        }

        return $doc;

    }

    public static function is_format(&$data) {
        if (is_string(&$data)) {

            if (strpos(&$data,'TY  -')!==false && strpos(&$data,'ER  -')!=0) {
                return true;
            }

        }
        return false;
    }

    /**
     * converts an xml string or xml dom to ris format
     * @data xml string or dom
     * returns object:
     */
    public function export(&$data,$options) {
        $supportedtags = &$this->supportedtags;
        $referencetypes = &$this->referencetypes;

        //if $data is a string turn into xml dom
        if (is_string(&$data)) {
            $data=parent::string_to_dom(&$data);
        }
        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }
        //get all the references from xml
        $reflist = $data->getElementsByTagName('reference');
        $risstring = '';
        for ($a = 0,$max = $reflist->length;$a<$max;$a++) {
            //Get starting TY (rt) tag first as this might not be first child node
            $rtnodes = $reflist->item($a)->getElementsByTagName('rt');
            if ($rtnodes->length==1) {
                //write TY line
                $rtnodeval = $this->search_replace_text($referencetypes,$rtnodes->item(0)->nodeValue,true);
                if ($rtnodeval == '') {
                    $rtnodeval = 'GEN';//there was no matching type so make generic
                }
                $risstring.= "\r\n".$this->search_replace_text($supportedtags,'rt',true)."  - ".$rtnodeval;
            } else {
                //we have a problem with this ref as no type, ignore it
                break;
            }
            //loop thru all child nodes
            for ($b = 0,$maxb = $reflist->item($a)->childNodes->length;$b<$maxb;$b++) {
                $nodenom=$reflist->item($a)->childNodes->item($b)->nodeName;
                $nodeval=$reflist->item($a)->childNodes->item($b)->nodeValue;
                //special case node names, changed to supported type in tags array
                switch($nodenom) {
                    case 'cn':
                        $nodenom='no';
                        break;
                    case 'ed':
                        $nodenom='vo';
                        break;
                }
                $nodenom = $this->search_replace_text($supportedtags,$nodenom,true);
                //write tag and value to ris string
                if ($nodenom!='' && $nodenom!='TY') {
                    $risstring.= "\r\n".$nodenom."  - ".trim(htmlspecialchars_decode($nodeval,ENT_QUOTES));
                }
            }
            //end tag to ris
            $risstring.= "\r\nER  - \r\n";
        }

        $retobj=new stdClass();
        $retobj->contents=$risstring;
        $retobj->file=$options->filename.'.txt';
        return $retobj;
    }

    //transformation functions (specific to ris class)

    /**
     * creates an xml element in $doc and appends to $recnode
     * Uses record as element name and val as value
     * Checks/conversions in place to convert from ris to xml format
     * @param $record string
     * @param $val string
     * @param $recnode xml node
     * @param $doc xml dom
     * @return nowt - driectly appends node
     */
    private function make_node($record,$val,&$recnode,&$doc) {

        $supportedtags = &$this->supportedtags;
        $referencetypes = &$this->referencetypes;

        $elementname='';
        $elementvalue=$val;
        //map array element names against xml elements
        $elementname=$this->search_replace_text($supportedtags,$record,false);

        //extra bits for some tags
        switch($elementname) {
            case 'rt':
                //change value to xml format compliant
                $elementvalue=$this->search_replace_text($referencetypes,$val,false);
                if ($elementvalue == '') {
                    $elementvalue = 'Generic';//If unsupported type default to generic
                }
                break;
            case 'no':
                //notes are changed to cn if a number
                //sometimes non numbers e.g. 11/2.88 are also cn
                //not sure how to test for this, so checking against pure numbers only
                if (!$this->isNaN($elementvalue)) {
                    $elementname='cn';
                }
                break;
            case 'vo':
                //volume is changed to ed if string
                if ($this->isNaN($elementvalue)) {
                    $elementname='ed';
                }
                break;
        }
        //append to node (if supported tag)
        if ($elementname!='') {
            $elementvalue=htmlspecialchars($elementvalue,ENT_NOQUOTES,"UTF-8",false);
            $childnode=$doc->createElement($elementname,$elementvalue);
            $recnode->appendChild($childnode);
        }
    }

    /**
     * takes a text string ris file and converts to associative array
     * @param $data string:ris file
     * @return array
     */
    private function ris_to_array($data) {
        $dataarray=explode("\n",$data);
        $retarray=array();
        $counter = 0;
        $hasertag=true;
        $curarray=NULL;
        for ($a=0,$max=count($dataarray);$a<$max;$a++) {
            $value=$dataarray[$a];
            //get first two chars (ltrim loads of characters, inc ascii and utf control chars)
            $tag=strtoupper(substr(ltrim($value,"\n\t\r\h\v\0 \x0B\x7f..\xff\x0..\x1f"),0,2));
            //if TY then start new array
            if ($tag=="TY") {
                if (!$hasertag) {
                    //previous record had no er tag, so do this before new record
                    $retarray[]=$curarray;
                }
                $hasertag = false;
                $curarray=array();
                //initialise counter
                $counter = 0;
            }
            if ($tag=="ER" && !$hasertag) {
                //last tag, add to main array (only if we have come across a TY)
                $retarray[]=$curarray;
                $hasertag = true;
                $curarray = NULL;//reset array so only TY can start record
            }else if ($tag!='' && $tag!='  ' && is_array($curarray)) {
                //get value after first - (removing leading whitespace) (only when TY encountered previously)
                $value=substr($value,strpos($value,'-',2)+1,strlen($value));
                $value=trim($value);
                $curarray[$counter][$tag]=$value;
                $counter++;
            }
        }
        if (!$hasertag && is_array($curarray)) {
            //somehow we got to end of process and no end tag reached: so add
            $retarray[]=$curarray;
        }
        return $retarray;
    }

    /**
     * Search through array sent and looks for match with search text - returns array value or empty if not found
     * @param $searchReplaceActionsArray
     * @param $sourceString
     * @param $inverse
     * @return string, blank if no match found
     */
    private function search_replace_text($searchreplaceactionsarray, $sourcestring, $inverse=false) {
        // apply the search & replace actions defined in '$searchReplaceActionsArray' to the text passed in '$sourceString':
        foreach ($searchreplaceactionsarray as $searchstring => $replacestring) {
            if (!$inverse) {
                //forward check/swap
                if ($sourcestring == $searchstring) {
                    return $replacestring;
                }
            } else {
                //inverse check/swap
                if ($sourcestring == $replacestring) {
                    return $searchstring;
                }
            }
        }
        return '';
    }

}

?>