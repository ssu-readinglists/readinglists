<?php
/**
 * rwrss
 * @author jp5987
 *
 *    RefWorks XML conversion class
 *    Standard format used by the library; all formats convert to/from this
 */


class rwxml extends references_convert_format{


    public function __construct() {

    }

    public static function can_import() {
        return true;
    }

    public static function can_export() {
        return true;
    }

    /**
     * Checks the xml (as already in correct format)
     * @data xml dom
     * returns xml dom:
     */
    public function import(&$data) {

        //if $data is a string turn into xml dom
        if (is_string(&$data)) {
            $data=parent::string_to_dom(&$data);
        }

        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }

        //create new xml and rip out refworks tag contents into a new references tag
        $nodes=$data->getElementsByTagName('reference');

        $doc = new DOMDocument('1.0','utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $newnode=$doc->createElement('references');
        $doc->appendChild($newnode);

        $i=0;

        while ( $node = $nodes->item($i) ) {

            $domNode = $doc->importNode($node, true);
            // append node
            $domNode->setAttribute('id',$domNode->getElementsByTagName('id')->item(0)->nodeValue);
            $newnode->appendChild($domNode);

            $i++;
        }

        return $doc;
    }

    //converts refxml to refworks xml and returns as string
    public function export(&$data,$options) {
        //if $data is a string turn into xml dom
       if (is_string(&$data)) {
            $data=parent::string_to_dom(&$data);
        }

        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }

        $doc = new DOMDocument('1.0','utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $newnode=$doc->createElement('refworks');
        $doc->appendChild($newnode);

        $newnode->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:refworks', 'www.refworks.com/xml/');

        $DomNodeList=$data->getElementsByTagName('reference');

        $i=0;

        while ( $node = $DomNodeList->item($i) ) {
            // import node
            $domNode = $doc->importNode($node, true);
            if ($domNode->hasAttribute('id')) {
                $domNode->removeAttribute('id');
            }
            if ($domNode->hasAttribute('src')) {
                $domNode->removeAttribute('src');
            }
            if ($domNode->hasAttribute('style')) {
                $domNode->removeAttribute('style');
            }
            if ($domNode->hasAttribute('title')) {
                $domNode->removeAttribute('title');
            }
            /*foreach ($domNode->childNodes as $child) {
                $child->nodeValue=htmlspecialchars($child->nodeValue,ENT_NOQUOTES,'UTF-8',false);
            }*/
            //remove folder nodes
            $fldnodes = $domNode->getElementsByTagName('fl');
            for ($a = 0; $a<$fldnodes->length; $a++) {
                $domNode->removeChild($fldnodes->item($a));
            }
            //Remove typeord
            $rtnodes = $domNode->getElementsByTagName('rt');
            for ($a = 0; $a<$rtnodes->length; $a++) {
                $rtnodes->item($a)->removeAttribute('typeOrd');
            }

            // append node
            $newnode->appendChild($domNode);
            $i++;
        }
        $output = $doc->saveXML();
        $retobj=new stdClass();
        $retobj->contents=$output;
        if (isset($options->filename)) {
            $retobj->file=$options->filename.'.xml';
        }
        return $retobj;
    }

    public static function is_format(&$data) {
        if (is_string(&$data)) {
            if (!$dom=parent::string_to_dom(&$data)) {
                return false;
            }
        } else {
            $dom=&$data;
        }

        if ($dom->getElementsByTagName('reference')->length==0) {
            return false;
        }

        return true;
    }
}

?>