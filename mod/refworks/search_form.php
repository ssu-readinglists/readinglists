
<?php
/**
 * Reference fields form
 * @author owen@ostephens.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

GLOBAL $CFG;
require_once($CFG->libdir.'/formslib.php');
//reference edit base form
class search_any_form extends moodleform {
    public $_formref;

            function definition() {
                require_once(dirname(__FILE__).'/search_base.php');
                $mform    =& $this->_form;
                $this->_formref =& $this->_form;

                //any array
                $mform->addElement('html', '<div class="mrefsearchformcontainer" id="container_searchany">');
                $searchanyarray=array();
                $searchanyradio=array();
                $searchanyarray[] =& $mform->createElement('text', 's_any', get_string('form_s_any', 'refworks'));
                $searchanyarray[] =& $mform->createElement('submit','get_any',get_string('form_s_any', 'refworks'));
                $mform->addGroup($searchanyarray, 'searchanyarray', get_string('form_s_any', 'refworks'), '', false);
                $searchanyradio[] =& $mform->createElement('radio', 's_type', '', get_string('doi', 'refworks'), 's_doi', '');
                $searchanyradio[] =& $mform->createElement('radio', 's_type', '', get_string('issn', 'refworks'), 's_issn', '');
                $searchanyradio[] =& $mform->createElement('radio', 's_type', '', get_string('isbn', 'refworks'), 's_isbn', '');
                $searchanyradio[] =& $mform->createElement('radio', 's_type', '', get_string('primorid',  'refworks'), 's_primoid', '');
                $mform->addGroup($searchanyradio, 'searchanyradio', '', '', false);
                $mform->setType('s_any', PARAM_TEXT);
                $mform->addElement('html', '</div>');
            }
}

class search_doi_form extends moodleform {
    public $_formref;

            function definition() {
                require_once(dirname(__FILE__).'/search_base.php');
                $mform    =& $this->_form;
                $this->_formref =& $this->_form;

                //DOI array
                $mform->addElement('html', '<div class="mrefsearchformcontainer" id="container_searchdoi">');
                $searchdoiarray=array();
                $searchdoiarray[] =& $mform->createElement('text', 's_doi', get_string('form_s_doi', 'refworks'));
                $searchdoiarray[] =& $mform->createElement('submit','get_doi',get_string('form_doi_get', 'refworks'));
                $mform->addGroup($searchdoiarray, 'searchdoiarray', get_string('form_s_doi', 'refworks'), '', false);
                $mform->setType('s_doi', PARAM_TEXT);
                $mform->addElement('html', '</div>');
            }
}

class search_isbn_form extends moodleform {
    public $_formref;

            function definition() {
                require_once(dirname(__FILE__).'/search_base.php');
                $mform    =& $this->_form;
                $this->_formref =& $this->_form;

                 //ISBN array
                $mform->addElement('html', '<div class="mrefsearchformcontainer" id="container_searchisbn">');
                $searchisbnarray=array();
                $searchisbnarray[] =& $mform->createElement('text', 's_isbn', get_string('form_s_isbn', 'refworks'));
                $searchisbnarray[] =& $mform->createElement('submit','get_isbn',get_string('form_isbn_get', 'refworks'));
                $mform->addGroup($searchisbnarray, 'searchisbnarray', get_string('form_s_isbn', 'refworks'), '', false);
                $mform->setType('s_isbn', PARAM_TEXT);
                $mform->addElement('html', '</div>');
            }
}

class search_issn_form extends moodleform {
    public $_formref;

            function definition() {
                require_once(dirname(__FILE__).'/search_base.php');
                $mform    =& $this->_form;
                $this->_formref =& $this->_form;

                 //ISSN array
                $mform->addElement('html', '<div class="mrefsearchformcontainer" id="container_searchissn">');
                $searchissnarray=array();
                $searchissnarray[] =& $mform->createElement('text', 's_issn', get_string('form_s_issn', 'refworks'));
                $searchissnarray[] =& $mform->createElement('submit','get_issn',get_string('form_issn_get', 'refworks'));
                $mform->addGroup($searchissnarray, 'searchissnarray', get_string('form_s_issn', 'refworks'), '', false);
                $mform->setType('s_issn', PARAM_TEXT);
                $mform->addElement('html', '</div>');
            }
}
class search_primorid_form extends moodleform {
    public $_formref;

            function definition() {
                require_once(dirname(__FILE__).'/search_base.php');
                $mform    =& $this->_form;
                $this->_formref =& $this->_form;

                 //ISSN array
                $mform->addElement('html', '<div class="mrefsearchformcontainer" id="container_searchprimorid">');
                $searchprimoridarray=array();
                $searchprimoridarray[] =& $mform->createElement('text', 's_primorid', get_string('form_s_primorid', 'refworks'));
                $searchprimoridarray[] =& $mform->createElement('submit','get_primorid',get_string('form_primorid_get', 'refworks'));
                $mform->addGroup($searchprimoridarray, 'searchprimoridarray', get_string('form_s_primorid', 'refworks'), '', false);
                $mform->setType('s_primorid', PARAM_TEXT);
                $mform->addElement('html', '</div>');
            }
}
?>