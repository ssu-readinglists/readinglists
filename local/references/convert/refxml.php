<?php
/**
 * convert
 * @author jp5987
 *
 *    conversion class which controls conversion of bibliography formats
 *    each format should have it's own class with the same method names (extend abstract format class)
 *    Also handles some data manipulation, e.g. getting a specific record
 *    This is not a static class, you need to create an instance
 *    Standard data format used is refworks style xml, data is passed around as xml dom
 */
//ADD CONVERSION FORMAT CLASSES HERE
require_once dirname(__FILE__).'/format.php';
require_once dirname(__FILE__).'/ris.php';
require_once dirname(__FILE__).'/rwrss.php';
require_once dirname(__FILE__).'/rwxml.php';
require_once dirname(__FILE__).'/refworks_directexport.php';
require_once dirname(__FILE__).'/ref_filter.php';

class refxml{

    public $ver='0.1';

    public $debug=false;

    protected $formats=array();

    private $curformatname='';

    private $curformatinstance=NULL;


    /**
     *
     * @param $formatname string: send format name if you already know which one you want
     * @return class instance
     */
    public function __construct($formatname='') {
        //NEED TO ALTER THIS EVERYTIME YOU ADD A CONVERSION FORMAT
        //array in format name>='' class>=
        $this->formats[]=array('name'=>'RIS', 'class'=>'ris');
        $this->formats[]=array('name'=>'RefWorksRSS', 'class'=>'rwrss');
        $this->formats[]=array('name'=>'RefWorksXML', 'class'=>'rwxml');
        $this->formats[]=array('name'=>'RefWorksDE', 'class'=>'references_convert_refworks_directexport');
        $this->formats[]=array('name'=>'MoodleFilter', 'class'=>'references_convert_ref_filter');

        if ($formatname!='') {
            $this->set_format($formatname);
        }
    }

    /**
     * Sets the current format type based on string sent
     * @param $formatname string:name of the format, must be defined in __construct
     * @return boolean succes
     */
    public function set_format($formatname) {
        foreach ($this->formats as $types) {
            if (strtolower($types['name'])==strtolower($formatname)) {
                $this->curformatname=$formatname;
                //check if no instance, or if instance is currently different
                if ($this->curformatinstance==NULL || !$this->curformatinstance instanceof $types['class']) {
                    //create an instance (not sure on garbage collection: so have reset to null first)
                    $this->curformatinstance=NULL;
                    $this->curformatinstance=new $types['class'];
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Calls the import function of the selected conversion type
     * @param $data string/dom: data to transform usually string
     * @param $format string:optional name of the format, must be defined in __construct
     * @param $sorting string:optional sorting param for format [if supported]
     * @return xml of all references after transform - returns false if failure
     */
    public function return_transform_in($data, $format=NULL, $sorting=NULL) {

        if (!isset($data) || $data=='') {
            return false;
        }

        if ($format!=NULL) {
            $this->set_format($format);
        }

        if ($this->curformatinstance==NULL) {
            return false;
        }


        if ($sorting==NULL) {
            $returnval=$this->curformatinstance->import($data);
        } else {
            $returnval=$this->curformatinstance->import($data,$sorting);
        }

        return $returnval;

    }

    /**
     * Calls the export function of the chosen format
     * @param $data mixed:data to process
     * @param $format string:name of format to use
     * @param $options mixed
     * @return mixed
     */
    public function transform_out(&$data, $format, $options) {

        if (!isset($data) || $data=='') {
            return false;
        }

        if ($format!=NULL) {
            $this->set_format($format);
        }

        if ($this->curformatinstance==NULL) {
            return false;
        }


        $returnval=$this->curformatinstance->export($data,$options);

        return $returnval;

    }

/**
     * Works out if the data you send matches any of the recognised formats
     * @param $data string:the data you want to test
     * @return string: name of the data type (based on ::$formats)
     */
    public function return_data_type(&$data) {
        foreach ($this->formats as $format) {
            if ($this->test_data_type($data,$format['name'])!==false) {
                return $format['name'];
            }
        }
    }

    /**
     * Test data against the currently selected type
     * @param $data string:text to test
     * @param $type string: test against specific type (based on ::$formats)
     * @return boolean
     */
    public function test_data_type(&$data,$type=false) {

        if (!$type) {
            $classname=$this->return_class_name($this->curformatname);
        } else {
            $classname=$this->return_class_name($type);
        }

        if (is_null($data) || $data == false) {
            return false;//always return false if null
        }

        return call_user_func(array($classname,'is_format'),&$data);
    }

    /**
     * Returns the class (file) name from $this->formats based on $format
     * @param $format string: class to search for
     * @return string of class name
     */
    private function return_class_name($format) {
        foreach ($this->formats as $class) {
            if ($class['name']==$format) {
                return $class['class'];
            }
        }
    }


    //helper functions that manipulate the reference data (once in xml)

    /**
     * Returns a flat donnodelist or xml string of references by node id value
     * @param $data dom element or xml string
     * @param $ids array: id numbers (or empty for all)
     * @param $asstring boolean: set to true to return string (e.g. for db export)
     * @param $asarray boolean: return the output string within an array
     * @return domnodelist/string
     */
    public function return_reference($data,$ids,$asstring=false,$asarray=false) {
        //if $data is a string turn into xml dom
        if (is_string($data)) {
            $data=$this->string_to_dom($data);
        }
        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }

        //make new dom and populate with correct nodes
        $doc = new DOMDocument('1.0','utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $xpath = new DOMXPath($data);
        for ($a=0,$max=count($ids);$a<$max;$a++) {
            $result = $xpath->query("//reference[@id='$ids[$a]']");
            if ($result->length>0) {
                $domNode = $doc->importNode($result->item(0), true);
                $doc->appendChild($domNode);
            }
        }
        //if sent no ids - get all
        if (count($ids)==0) {
            $refs=$data->getElementsByTagName('reference');
            foreach ($refs as $ref) {
                $domNode = $doc->importNode($ref, true);
                $doc->appendChild($domNode);
            }
        }

        if ($asstring) {
            if ($asarray) {
                //array of xml strings
                $retarray=array();
                $refstring=$doc->saveXML();
                //replace any tags not needed in string
                $refstring=str_ireplace('<?xml version="1.0" encoding="utf-8"?>','',$refstring);
                $refstring=str_ireplace('<references>','',$refstring);
                $refstring=str_ireplace('</references>','',$refstring);
                $retarray=explode('</reference>',$refstring);
                //add closing tag back to end of each array element
                for ($a=0,$max=count($retarray);$a<$max;$a++) {
                    $retarray[$a]=trim($retarray[$a]);
                    $retarray[$a].='</reference>';
                }
                //check last element is not empty
                if ($retarray[$max-1]=='</reference>') {
                    array_pop($retarray);
                }
                return $retarray;
            } else {
                //xml string of all
                return $doc->saveXML();
            }
        } else {
            return $doc->getElementsByTagName('reference');
        }

    }

    /**
     * Returns list of all references from a set of xml references
     * @param $data dom: xml or xml string
     * @param $forquikbib : return as a string ready for sending to quikbib
     * @param $src string: set to the src value to get all refs from that src
     * @return domNodeList or string
     */
    public function return_references($data,$forquikbib=false,$src='') {
        //if $data is a string turn into xml dom
        if (is_string($data)) {
            $data=$this->string_to_dom($data);
        }
        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }


        if ($src=='') {
            $nodes=$data->getElementsByTagName('reference');
        } else {
            $xpath = new DOMXPath($data);
            $xpath->registerNamespace ( 'xml' , 'http://www.open.ac.uk/refdata' );
            $nodes = $xpath->query("//xml:reference[@src='$src']");
        }

        if ($forquikbib) {
            return $this->domnodelist_to_string($nodes,$forquikbib);
        } else {
            return $nodes;
        }
    }

    /**
     * Sorts a dom doc object of references based on field
     * @param $data dom:
     * @param $field string:field you want to sort against
     * @return success
     */
    public function sort_references(&$data,$field) {
        $field=strtolower($field);
        $sorttype='text';
        //change any text to correct node name
        switch($field) {
            case "title":$field='t1';
                break;
            case "author":$field='a1';
                break;
            case "creator":$field='a1';
                break;
            case "date":$field='yr';
                        $sorttype='number';
                break;
        }

        $xsl = new DOMDocument();
        if (!$xsl->load(dirname(__FILE__).'/xmlsort.xsl')) {
            return false;
        }

        // Create new XSLTProcessor
        $xslt = new XSLTProcessor();
        // Load stylesheet
        if (!$xslt->importStylesheet($xsl)) {
            return false;
        }

        //sorting
        $xslt->setParameter('','sortby',$field);

        $xslt->setParameter('','sorttype',$sorttype);


        if (!$data = $xslt->transformToDoc($data)) {
            return false;
        }

        return true;
    }


    /*
     * Convert a dom node list to a string of xml
     * oliver dot christen at camptocamp dot com
     * http://uk3.php.net/manual/en/domnodelist.item.php
     */
    private function domnodelist_to_string($DomNodeList,$refworks=false) {
        $output = '';
        $i=0;
        $doc = new DOMDocument('1.0','utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        //create a refworks node as top level to returned xml
        if ($refworks) {
            $newnode=$doc->createElement('refworks');
            $doc->appendChild($newnode);

            $newnode->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:refworks', 'www.refworks.com/xml/');
        }


        while ( $node = $DomNodeList->item($i) ) {
            // import node
            if ($node->hasAttribute('id')) {
                $node->removeAttribute('id');
            }
            if ($node->hasAttribute('src')) {
                $node->removeAttribute('src');
            }
            if ($node->hasAttribute('style')) {
                $node->removeAttribute('style');
            }
            if ($node->hasAttribute('title')) {
                $node->removeAttribute('title');
            }

            $domNode = $doc->importNode($node, true);
            // append node
            if (!$refworks) {

                $doc->appendChild($domNode);
            } else {
                //remove unwanted sub tags
                $domElemsToRemove = array();
                $domNodeList = $domNode->getElementsByTagname('fl');
                foreach ( $domNodeList as $domElement ) {
                    $domElemsToRemove[] = $domElement;
                }
                //Now remove from dom
                foreach ( $domElemsToRemove as $domElement ) {
                    $domElement->parentNode->removeChild($domElement);
                }
                $newnode->appendChild($domNode);
            }
            $i++;
        }
        $output = $doc->saveXML();
        //$output = print_r($output, 1);
        return $output;
    }

    /**
     * Converts a string of xml to a dom object
     * @param $str string: xml string
     * @return dom (or false if error)
     */
    private function string_to_dom($str) {
        //convert all strings to utf-8
        if (mb_detect_encoding($str, "UTF-8, ASCII, ISO-8859-1")!="UTF-8") {
            $str=utf8_encode($str);
        }
        //check for xml declarartion
        if (strpos($str,'<?xml')===false) {
            return false;
        }
        $data = new DOMDocument('1.0','utf-8');
        $data->preserveWhiteSpace = false;
        $data->formatOutput = false;
        //remove any white space prior to declarartion
        $str = ltrim($str,"\n\t\r\h\v\0 \x0B\x7f..\xff\x0..\x1f");
        if ($data->loadXML($str)===false) {
            return false;
        }
        return $data;
    }

}

?>