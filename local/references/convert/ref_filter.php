<?php
/**
 * Puts ref xml that can be added by the user into activities and then converted into xhtml (with export features) by a filter
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local/references/convert/
 */


class references_convert_ref_filter extends references_convert_format{


    public function __construct() {

    }

    public static function can_import() {
        return false;
    }

    public static function can_export() {
        return true;
    }

    //no import
    public function import(&$data) {

    }

    /**
     * Displays html that can be copied by end-user and pasted into a moodle tool
     * @see local/references/convert/format#export()
     */
    public function export(&$data,$options) {

        //if $data is a string turn into xml dom
        if (is_string(&$data)) {
            $data=parent::string_to_dom(&$data);
        }

        //check is DOMDocument
        if (!$data instanceOf DOMDocument) {
            return false;
        }

        $reflist = $data->getElementsByTagName('reference');
        $i=0;
        //Depending on what module has called this the xml has different top-level elements
        //If no references tag make new xml and add in reference tags
        if ($data->getElementsByTagName('references')->length == 0) {
            $doc = new DOMDocument('1.0','utf-8');
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = false;

            $newnode=$doc->createElement('references');
            $doc->appendChild($newnode);

            while ( $node = $reflist->item($i) ) {
                // import node
                $domNode = $doc->importNode($node, true);
                // append node
                $newnode->appendChild($domNode);
                $i++;
            }

            $data = $doc;

        } else {
            //tidy up the xml
            //remove any attributes from the reference tags
            while ( $node = $reflist->item($i) ) {
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
                $i++;
            }
        }

        //Remove unwanted nodes (id, fl, ab, k1, cr, cd, md, ol[unknown or english])

        $domElemsToRemove = array();//array of dom ele to remove
        $domNodeList = $data->getElementsByTagname('id');
        foreach ( $domNodeList as $domElement ) {
            $domElemsToRemove[] = $domElement;
        }
        $domNodeList = $data->getElementsByTagname('fl');
        foreach ( $domNodeList as $domElement ) {
            $domElemsToRemove[] = $domElement;
        }
        $domNodeList = $data->getElementsByTagname('k1');
        foreach ( $domNodeList as $domElement ) {
            $domElemsToRemove[] = $domElement;
        }
        $domNodeList = $data->getElementsByTagname('ab');
        foreach ( $domNodeList as $domElement ) {
            $domElemsToRemove[] = $domElement;
        }
        $domNodeList = $data->getElementsByTagname('cr');
        foreach ( $domNodeList as $domElement ) {
            $domElemsToRemove[] = $domElement;
        }
        $domNodeList = $data->getElementsByTagname('cd');
        foreach ( $domNodeList as $domElement ) {
            $domElemsToRemove[] = $domElement;
        }
        $domNodeList = $data->getElementsByTagname('md');
        foreach ( $domNodeList as $domElement ) {
            $domElemsToRemove[] = $domElement;
        }
        $domNodeList = $data->getElementsByTagname('ol');
        foreach ( $domNodeList as $domElement ) {
            if (strpos($domElement->nodeValue,'Unknown(0)')!==false || strpos($domElement->nodeValue,'English')!==false) {
                $domElemsToRemove[] = $domElement;
            }
        }
        //Now remove from dom
        foreach ( $domElemsToRemove as $domElement ) {
            $domElement->parentNode->removeChild($domElement);
        }

        //remove typeord att
        $domNodeList = $data->getElementsByTagname('rt');
        foreach ( $domNodeList as $domElement ) {
            if ($domElement->hasAttribute('typeOrd')) {
                $domElement->removeAttribute('typeOrd');
            }
        }

        //remove doctype and get XML at text
        $result = preg_replace('/<!DOCTYPE[^>]*>/','',$data->saveXML($data->documentElement));
        $result = str_replace('&amp;','&',$result);//stops double encoding
        //get rid of whitespace
        $result = str_replace(array("\n", "\r", "\t", "  "),'',$result);
        $result = htmlspecialchars($result, ENT_COMPAT, 'utf-8',false);

        if (isset($options->unittest)) {
            //for any unit tests - don't print (also used generally just to get text with this format)
            return $result;
        }

        print_string('ref_filter_instruct','local_references');

        $editor = editors_get_preferred_editor(FORMAT_PLAIN);
        $editor->use_editor('ref_filter_text', array('noclean'=>true));
        echo '<div class="form-textarea"><textarea rows="10" cols="80" id="ref_filter_text" name="ref_filter_text">'. $result .'</textarea></div>';

        echo("<div id='ref_filter_text_copy'></div>");
        //Create a button that allows you to add the text to the clipboard
        $output=<<<SCRIPT
<script type="text/javascript" language="javascript">
function copy_clip()
{
 if (document.body.createTextRange)
   {
   // IE
   var sText = document.getElementById("ref_filter_text").createTextRange();
   sText.execCommand("Copy");
   }
   return false;
};
addonload(function() {document.getElementById("ref_filter_text").focus();document.getElementById("ref_filter_text").select()});
SCRIPT;

        $output.='if (document.body.createTextRange) {document.getElementById("ref_filter_text_copy").innerHTML="<input type=\"button\" value=\"'.get_string('clipboardcopy','local_references').'\" onclick=\"copy_clip()\" />"};';
        $output.="</script>";

        echo $output;
    }

    public static function is_format(&$data) {
        return false;
    }
}

?>