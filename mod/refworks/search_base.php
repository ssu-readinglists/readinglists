<?php
/**
 * Base (static) class for the refworks module.
 * Should be included on all pages
 * Contains methods for general page use - e.g. initialisation, headers etc
 *
 * @author owen@ostephens.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

GLOBAL $CFG;
require_once($CFG->libdir.'/formslib.php');

class search_base {
	public static $retrievedarray = array();
    public static $lasterror = '';
    public static $errorflag;
    // Maybe have an array defining order for searching?
    public static $search_sources = array('worldcat','crossref','primo');

    function search_worldcat() {

    }

    function search_crossref() {
	// How to pass search params?
	// Just assume that we know the types that can be passed?
	// e.g. only pass doi to crossref?


    }

    function search_primo() {

    }

    public static function search_form() {
        global $OUTPUT;
        require_once(dirname(__FILE__).'/../../local/references/getdata.php');
        require_once(dirname(__FILE__).'/search_form.php');
        $doi_s_form = new search_doi_form();
        $isbn_s_form = new search_isbn_form();
        $issn_s_form = new search_issn_form();

        // Show header
        echo $OUTPUT->box_start('generalbox', 'resourcepage_reference');

        echo '<div id="searchfields">';
        echo '<div id="searchdoi">';
        $doi_s_form->display();
        echo '</div><div id="searchisbn">';
        $isbn_s_form->display();
        echo '</div><div id="searchissn">';
        $issn_s_form->display();
        echo '</div>';
        echo $OUTPUT->box_end();
    }

    
}


?>