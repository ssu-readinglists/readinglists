<?php
/**
 * rwrss
 * @author jp5987
 *
 *    RefWorks RSS conversion class
 */


class rwrss extends references_convert_format{


    public function __construct() {

    }

    public static function can_import() {
        return true;
    }

    public static function can_export() {
        return false;
    }

    /**
     * imports a rss feed & converts to array format - then to xml?
     * @data rss dom
     * returns xml dom or false if failed
     */
    public function import(&$data,$sorting='') {

        //if $data is a string turn into xml dom
        if (is_string(&$data)) {
            $data=parent::string_to_dom(&$data);
        }

        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }

        //RSS feed text is all in cdata, refxml is not.
        //There may be illegal chars in text, so go thru every node and make sure
        $allitems=&$data->getElementsByTagName('item');
        foreach ($allitems as $item) {
            foreach ($item->childNodes as $child) {
                $child->nodeValue=htmlspecialchars($child->nodeValue,ENT_NOQUOTES,'UTF-8',false);
            }
        }

        // Load XSL template
        $xsl = new DOMDocument();
        if (!$xsl->load(dirname(__FILE__).'/rss2xml.xsl')) {
            return false;
        }

        // Create new XSLTProcessor
        $xslt = new XSLTProcessor();
        // Load stylesheet
        if (!$xslt->importStylesheet($xsl)) {
            return false;
        }

        //sorting
        $xslt->setParameter('','sortby',$sorting);
        if ($sorting=='date'||$sorting=='created'||$sorting=='modified') {
            $xslt->setParameter('','sorttype','number');
        }

        $results = $xslt->transformToDoc(&$data);

        return $results;
    }

    /**
     * Checks is data string is a refworks rss
     * @param $data
     * @return true/false
     */
    public static function is_format(&$data) {
        if (is_string(&$data)) {
            if (!$dom=parent::string_to_dom(&$data)) {
                return false;
            }
        } else {
            $dom=&$data;
        }

        //x-path: used to query rss structure
        $xpath = new DOMXPath($dom);

        if (!$dom->isDefaultNamespace('http://purl.org/rss/1.0/')) {
            return false;
        }

        $xpath->registerNamespace ( 'rss' , 'http://purl.org/rss/1.0/' );

        $checkRefWorks = $xpath->query('//rss:channel/refworks:publisher');

        if ($checkRefWorks->length==0) {
            return false;
        }

        return true;
    }

    public function export(&$data,$options) {

    }
}

?>