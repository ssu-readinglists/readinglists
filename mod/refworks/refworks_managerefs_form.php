<?php
/**
 * Reference fields form
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

GLOBAL $CFG;
require_once($CFG->libdir.'/formslib.php');
//reference edit base form
class refworks_managerefs_form extends moodleform {
    public $_formref;

    public static $showtoggle = false; //flag for link to show/hide extra fields
    //names of all the possible reference types
    // Added Explicit numbering to this array for the Reference Form (for creation and editing) 
    // Explicit numbering means order can be controlled without changing the numbering, which is used elsewhere (e.g. by getdata.php)
    // Order here (5,4,3,14, 30,22,17,29,9,8,23,1...) reflects requirements of Southampton Solent
    // added by owen@ostephens.com for Southampton Solent University 09/03/2012
    public static $reftypes = array(
            5 => 'Book, Whole',
            4 => 'Book, Section',
            3 => 'Book, Edited',
            14 => 'Journal Article',
            30 => 'Web Page',
            22 => 'Newspaper Article',
            17 => 'Magazine Article',
            29 => 'Video/DVD',
            9 => 'Dissertation/Thesis',
            8 => 'Conference Proceedings',
            26 => 'Report',
            23 => 'Online Discussion Forum/Blogs',
            1 => 'Artwork',
            11 => 'Generic',
            0 => 'Abstract',
            2 => 'Bills/Resolutions',
            6 => 'Case/Court Decisions',
            7 => 'Computer Program',
            10 => 'Dissertation/Thesis Unpublished',
            12 => 'Grant',
            13 => 'Hearing',
            16 => 'Laws/Statutes',
            18 => 'Map',
            19 => 'Monograph',
            20 => 'Motion Picture',
            21 => 'Music Score',
            24 => 'Patent',
            25 => 'Personal Communication',
            27 => 'Sound Recording',
            15 => 'Journal, Electronic',
            28 => 'Unpublished Material'
            );

     //supported field names (names should match names of fields in form)
    // Commented out those not required by SSU. Added lk,av,db,jo,k1 required by SSU. owen@ostephens.com. 22nd May 2012
     public static $reffields = array(
         'rt',
         'a1',
         't1',
         't2',
         'jf',
         'sr',
         'yr',
         'rd',
         'fd',
         'vo',
         'ed',
         'is',
         'sp',
         'op',
         'pb',
         'pp',
         'a2',
         'sn',
         'do',
         'lk',
         'av',
         'db',
         'jo',
         'k1',
//         'ul',
//         'u1',
//         'u2',
//         'u3',
         'u5',
         'no',
//         'wt'
     );

            function definition() {

                $mform    =& $this->_form;
                $this->_formref =& $this->_form;
                //-------------------------------------------------------------------------------
                // Add General box
                //$mform->addElement('header', 'general', get_string('general', 'form'));

                //REF TYPE (rt)
                $mform->addElement('select', 'rt', get_string('form_reftype', 'refworks'), self::$reftypes);

                if (self::$showtoggle==true) {
                    $mform->addElement('html', '<div class="mrefformcontainer" id="createref_container_toggle">');
                    $mform->addElement('html', '</div>');
                }

                //DOI array
/*
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_searchdoi">');
                $mform->registerNoSubmitButton('get_data');
                $searchdoiarray=array();
                $searchdoiarray[] =& $mform->createElement('text', 's_do', get_string('form_s_doi', 'refworks'));
                $searchdoiarray[] =& $mform->createElement('submit','get_data',get_string('form_doi_get', 'refworks'));
                $mform->addGroup($searchdoiarray, 'searchdoiarray', get_string('form_s_doi', 'refworks'), '', true);
                $mform->setType('s_do', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                 //ISBN array
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_searchisbn">');
                $mform->registerNoSubmitButton('get_data_isbn');
                $searchisbnarray=array();
                $searchisbnarray[] =& $mform->createElement('text', 's_sn', get_string('form_s_isbn', 'refworks'));
                $searchisbnarray[] =& $mform->createElement('submit','get_data_isbn',get_string('form_isbn_get', 'refworks'));
                $mform->addGroup($searchisbnarray, 'searchisbnarray', get_string('form_s_isbn', 'refworks'), '', true);
                $mform->setType('s_sn', PARAM_TEXT);
                $mform->addElement('html', '</div>');
*/
                //DOI (do)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_doi">');
                $mform->addElement('text', 'do', get_string('form_doi', 'refworks'));
                $mform->setType('do', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //ISBN/ISSN (sn)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_sn">');
                $mform->addElement('text', 'sn', get_string('form_isbn', 'refworks'));
                $mform->setType('sn', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //AUTHORS (a1)*
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_authors">');
                $mform->addElement('text', 'a1', get_string('form_authors', 'refworks'));
                $mform->setType('a1', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //editor (a2)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_editor">');
                $mform->addElement('text', 'a2', get_string('form_editor', 'refworks'));
                $mform->setType('a2', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //Pub Year (yr)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_year">');
                $mform->addElement('text', 'yr', get_string('form_year', 'refworks'));
                $mform->setType('yr', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //TITLE (t1)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_title">');
                $mform->addElement('text', 't1', get_string('form_title', 'refworks'));
                $mform->setType('t1', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //TITLE2 (t2)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_title2">');
                $mform->addElement('text', 't2', get_string('form_title2', 'refworks'));
                $mform->setType('t2', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //website title (wt)
                /*$mform->addElement('html', '<div class="mrefformcontainer" id="container_website_title">');
                $mform->addElement('text', 'wt', get_string('form_wt', 'refworks'));
                $mform->setType('wt', PARAM_TEXT);
                $mform->addElement('html', '</div>');*/

                //Periodical, Full (jf)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_periodical">');
                $mform->addElement('text', 'jf', get_string('form_periodical', 'refworks'));
                $mform->setType('jf', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //Volume (vo)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_volume">');
                $mform->addElement('text', 'vo', get_string('form_volume', 'refworks'));
                $mform->setType('vo', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //edition (ed)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_edition">');
                $mform->addElement('text', 'ed', get_string('form_edition', 'refworks'));
                $mform->setType('ed', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //Issue (is)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_issue">');
                $mform->addElement('text', 'is', get_string('form_issue', 'refworks'));
                $mform->setType('is', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //Start page/total pages (sp)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_page">');
                $mform->addElement('text', 'sp', get_string('form_page', 'refworks'));
                $mform->setType('sp', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //Other page (op)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_otherpage">');
                $mform->addElement('text', 'op', get_string('form_otherpage', 'refworks'));
                $mform->setType('op', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //place of pub (pp)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_placepub">');
                $mform->addElement('text', 'pp', get_string('form_placepub', 'refworks'));
                $mform->setType('pp', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //publisher (pb)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_publisher">');
                $mform->addElement('text', 'pb', get_string('form_publisher', 'refworks'));
                $mform->setType('pb', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                //Pub Date Free From (fd) added for Newspaper Article
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_pub_date_free">');
                $mform->addElement('text', 'fd', get_string('form_pub_date_free', 'refworks'));
                $mform->setType('fd', PARAM_TEXT);
                $mform->addElement('html', '</div>');
// Not required by SSU. owen@ostephens.com 28th March 2012
/*
                //url (ul)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_url">');
                $mform->addElement('text', 'ul', get_string('form_url', 'refworks'));
                $mform->setType('ul', PARAM_TEXT);
                $mform->addElement('html', '</div>');
*/
                // Commented out OU specific fields as not required by SSU
                // owen@ostephens.com 28th March 2012
                /*
                //OU specific fields
                //$mform->addElement('header', 'general', get_string('form_OU', 'refworks'));
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_u1">');
                $mform->addElement('text', 'u1', get_string('form_u1', 'refworks'));
                $mform->setType('u1', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                $mform->addElement('html', '<div class="mrefformcontainer" id="container_u2">');
                $mform->addElement('text', 'u2', get_string('form_u2', 'refworks'));
                $mform->setType('u2', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                $mform->addElement('html', '<div class="mrefformcontainer" id="container_u3">');
                $mform->addElement('text', 'u3', get_string('form_u3', 'refworks'));
                $mform->setType('u3', PARAM_TEXT);
                $mform->addElement('html', '</div>');
*/
                // Additional fields for SSU. owen@ostephens.com 22nd May 2012
                // Availability (av)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_av">');
                $mform->addElement('text', 'av', get_string('form_av', 'refworks'));
                $mform->setType('av', PARAM_TEXT);
                $mform->addElement('html', '</div>');
                
                // Link (lk)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_lk">');
                $mform->addElement('text', 'lk', get_string('form_lk', 'refworks'));
                $mform->setType('lk', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                // Database (db)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_db">');
                $mform->addElement('text', 'db', get_string('form_db', 'refworks'));
                $mform->setType('db', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                // Journal Abbreviation (jo)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_jo">');
                $mform->addElement('text', 'jo', get_string('form_jo', 'refworks'));
                $mform->setType('jo', PARAM_TEXT);
                $mform->addElement('html', '</div>');

                // Descriptors (k1)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_k1">');
                $mform->addElement('text', 'k1', get_string('form_k1', 'refworks'));
                $mform->setType('k1', PARAM_TEXT);
                $mform->addElement('html', '</div>');
                
                //Date retrieved (rd)
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_retrieved">');
                $mform->addElement('text', 'rd', get_string('form_retrieved', 'refworks'));
                $mform->setType('rd', PARAM_TEXT);
                $mform->addElement('html', '</div>');
                // End of Additional fields for SSU

                //u5 - url override (staff cap only)
                if (refworks_base::check_capabilities('mod/refworks:allow_url_override')) {
                    $mform->addElement('html', '<div class="mrefformcontainer" id="container_u5">');
                    $mform->addElement('text', 'u5', get_string('form_u5', 'refworks'));
                    $mform->setType('u5', PARAM_TEXT);
                    $mform->addElement('html', '</div>');
                } else {
                    $mform->addElement('hidden', 'u5', '');
                }

                //Source type
                $sourcetypes = array(
                    'Print(0)' => get_string('form_sourcetype0', 'refworks'),
                    'Electronic(1)' => get_string('form_sourcetype1', 'refworks')
                );
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_sourcetype">');
                $mform->addElement('select', 'sr', get_string('form_sourcetype', 'refworks'), $sourcetypes);
                $mform->addElement('html', '</div>');
                
                //Notes field
                $mform->addElement('html', '<div class="mrefformcontainer" id="container_no">');
                $mform->addElement('textarea', 'no', get_string('form_no', 'refworks'));
                $mform->setType('no', PARAM_TEXT);
                // Auto notes functionality added for SSU. owen@ostephens.com 21 September 2011
                // Will only show for those who are also allowed to override URLs in u5
                if(refworks_base::check_capabilities('mod/refworks:allow_url_override')){
                    $mform->addElement('html','<br/><a href="#" onclick="autoNotes(\''.get_string('autonote_1', 'refworks').'\');return false;">Add Note: '.get_string('autonote_1', 'refworks').'</a>');
                    $mform->addElement('html','<br/><a href="#" onclick="autoNotes(\''.get_string('autonote_2', 'refworks').'\');return false;">Add Note: '.get_string('autonote_2', 'refworks').'</a>');
                    $mform->addElement('html','<br/><a href="#" onclick="autoNotes(\''.get_string('autonote_3', 'refworks').'\');return false;">Add Note: '.get_string('autonote_3', 'refworks').'</a>');
                    $mform->addElement('html','<br/><a href="#" onclick="autoNotes(\''.get_string('autonote_4', 'refworks').'\');return false;">Add Note: '.get_string('autonote_4', 'refworks').'</a>');
                    $mform->addElement('html','<br/><a href="#" onclick="autoNotes(\''.get_string('autonote_5', 'refworks').'\');return false;">Add Note: '.get_string('autonote_5', 'refworks').'</a>');
                }
                $mform->addElement('html', '</div>');
            }
}
?>