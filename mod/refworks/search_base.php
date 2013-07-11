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

    function search_worldcat {

    }

    function search_crossref {
	// How to pass search params?
	// Just assume that we know the types that can be passed?
	// e.g. only pass doi to crossref?


    }

    function search_primo {

    }

    
}
// may want this class in separate file
class search_form extends moodle_form {
	public $_formref;
	function definition($array_of_identifiers) {
		// Form for searching - i.e. place to enter DOI, ISSN, ISBN, System Number ....
		// Should present at top of page
		// $array_of_identifiers is e.g. [issn->1234-5678,doi->12123424.124,isbn->1234567890]
		// use array to populate form if these values already exist?
		// alternatively pass each value and list out?
		$mform =& $this->_form;
        $this->_formref =& $this->_form;
        // need to have a form to display search fields and buttons. Maybe list fields in array and then spit out each one in turn?
        $mform->addElement('html', '<div class="mrefformcontainer" id="container_doi">');
        $mform->registerNoSubmitButton('get_data');
        $doiarray=array();
        $doiarray[] =& $mform->createElement('text', 'do', get_string('form_doi', 'refworks'));
        $doiarray[] =& $mform->createElement('submit','get_data',get_string('form_doi_get', 'refworks'));
        $mform->addGroup($doiarray, 'doiarray', get_string('form_doi', 'refworks'), '', false);
        $mform->setType('do', PARAM_TEXT);
        $mform->addElement('html', '</div>');

         //ISBN array
        $mform->addElement('html', '<div class="mrefformcontainer" id="container_isbn">');
        $mform->registerNoSubmitButton('get_data_isbn');
        $isbnarray=array();
        $isbnarray[] =& $mform->createElement('text', 'sn', get_string('form_isbn', 'refworks'));
        $isbnarray[] =& $mform->createElement('submit','get_data_isbn',get_string('form_isbn_get', 'refworks'));
        $mform->addGroup($isbnarray, 'isbnarray', get_string('form_isbn', 'refworks'), '', false);
        $mform->setType('sn', PARAM_TEXT);
        $mform->addElement('html', '</div>');
	}
}

?>