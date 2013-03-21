<?php
/**
 * Abstract class used to define reports.
 * Report classes must be called refworks_report_...
 *
 * @copyright &copy; 2010 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks/reports/
 */

abstract class refworks_report{

    //main method that needs to be overriden by each report

    /*
     * Runs a report
     * @param $data string: xml of references
     */
    abstract public static function run_report($data);

    //shared methods

    /**
     * Converts a string of xml to a dom object
     * @param $str string: xml string
     * @return dom (or false if error)
     */
    public static function string_to_dom($str) {
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
        set_error_handler(array("refworks_report", "HandleXmlError"));
        //remove any white space prior to declarartion
        $str = ltrim($str,"\n\t\r\h\v\0 \x0B\x7f..\xff\x0..\x1f");
        if ($data->loadXML($str)===false) {
            return false;
        }
        restore_error_handler();
        return $data;
    }

    static function HandleXmlError($errno, $errstr, $errfile, $errline) {
        if ($errno==E_WARNING && (substr_count($errstr,"DOMDocument::loadXML()")>0))
        {
            throw new moodle_exception('reference_xmlloaderror', 'local_references');
        } else {
            return false;
        }
    }
}

?>