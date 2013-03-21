<?php
/**
 * Unit [simple] Test class for the refworks module. (for initial refworks api connection tests see local/references/...)
 * Tests:
 * refworks_ref_api
 * refworks_folder_api
 *
 * @copyright &copy; 2010 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package references
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
global $CFG;

require_once($CFG->libdir. '/simpletestlib/unit_tester.php');
require_once($CFG->libdir. '/simpletestlib/mock_objects.php');
require_once($CFG->libdir. '/simpletestlib.php');

require_once(dirname(__FILE__).'/../../../local/references/rwapi/rwapi.php');
require_once(dirname(__FILE__).'/../refworks_ref_api.php');
require_once(dirname(__FILE__).'/../refworks_folder_api.php');
require_once(dirname(__FILE__).'/../refworks_connect.php');
require_once(dirname(__FILE__).'/../refworks_base.php');

// Include the code to test
/** This class contains the test cases for the functions in editlib.php. */
class refworks_test extends UnitTestCase {

    protected static $connectok=false; //connected to rwapi ok

    protected static $refstring='';

    public function setUp() {
        self::$refstring=<<<XMLSTRING
<reference>
<rt>Book, Whole</rt>
<a1>Unit,Moodle</a1>
<a1>Open University</a1>
<t1>Unit Test Ref from Moodle</t1>
<yr>2010</yr>
<k1>Study</k1>
<pb>Open University</pb>
<pp>Milton Keynes</pp>
</reference>
XMLSTRING;

        global $CFG;
        if (isset($CFG->proxyhost)) {
            rwapi::$proxy = $CFG->proxyhost;
            if (isset($CFG->proxyport)) {
                rwapi::$proxyport = $CFG->proxyport;
            }
        }
        self::$connectok = rwapi::check_session('','VLE_account','VLEaccPa$$word');
    }
    public function tearDown() {
        if (self::$connectok) {
            rwapi::destroy_session();
        }
    }
    //Test for refworks_ref_api.php
    function test_ref_api() {
        $this->assertNotEqual(self::$connectok,false,'Connect to VLE refworks account.'.rwapi::$lasterror);
        //tests ref_api class methods (must have a valid session)
        if (self::$connectok) {

            //test creating a reference into a folder
            $result = refworks_ref_api::create_ref(self::$refstring,'UnitTest');
            $this->assertNotEqual($result,false,'Create test reference. [refworks_ref_api::create_ref]');
            $starttagpos = strpos($result,'<id>');
            if ($result!==false && $starttagpos!==false) {
                //work out the id so we can re-use this info later
                $refid=substr($result,$starttagpos+4,strpos($result,'</id>')-$starttagpos-4);
                //try updating some info
                $refstring = substr(self::$refstring,0,11).'<id>'.$refid.'</id><no>INSERT</no>'.substr(self::$refstring,11);
                $result = refworks_ref_api::update_ref($refstring);
                $this->assertNotEqual($result,false,'Update test reference.[refworks_ref_api::update_ref]');
                //then try and check the info is there and can be retrieved
                $result = refworks_ref_api::get_ref_data('&id='.$refid);

                $this->assertPattern("/INSERT/",$result,'Retrieve updated ref.[refworks_ref_api::get_ref_data]');
                //test we can find ref again
                $result = refworks_ref_api::get_all_refs('1','99',9,-1,'html','UnitTest');
                $this->assertPattern("/$refid/",$result,'Retrieve ref list.[refworks_ref_api::get_all_refs]');
                //check merge id list works and doesn't return a duplicated id
                $result = refworks_ref_api::merge_id_list($refid.',1','UnitTest');
                $foundid = array_keys($result,$refid);
                $this->assertEqual(count($foundid),1,'Merge id + folder with id[refworks_ref_api::merge_id_list]');
                //Now test getting a styled output for each style in the class
                foreach (refworks_ref_api::get_reference_styles() as $style) {
                    $result = refworks_ref_api::get_citations(array($refid),$style['quikbib']);
                    $this->assertNotEqual($result,false,'Get citations in style '.get_string($style['string'],'refworks').'.[refworks_ref_api::get_citations]');
                }
                //test deleting ref
                $result = refworks_ref_api::delete_ref($refid);
                $this->assertNotEqual($result,false,'Delete test reference.[refworks_ref_api::delete_ref]');
            }
        }
    }

    //tests refworks_folder_api.php
    function test_folder_api() {
        if (self::$connectok) {
            refworks_connect::$connectok = true;
            //TEST folder creation
            $result = refworks_folder_api::create_folder('UnitTestTemp');
            $this->assertNotEqual($result,false,'Create folder.[refworks_folder_api::create_folder]');
            //TEST folder list population
            $result = refworks_folder_api::update_folder_list();
            $this->assertNotEqual($result,false,'Get folder list.[refworks_folder_api::update_folder_list]');
            if ($result!==false) {
                $foundfld = false;
                foreach (refworks_folder_api::$folders as $folder) {
                    if ($folder['name']=='UnitTestTemp') {
                        $foundfld = true;
                    }
                }
                $this->assertNotEqual($foundfld,false,'Find created folder.[refworks_folder_api::create_folder/update_folder_list]');
            }
            //Create a reference and get its id
            $result = refworks_ref_api::create_ref(self::$refstring,'UnitTestTemp');
            $starttagpos = strpos($result,'<id>');
            if ($result!==false && $starttagpos!==false) {
                //work out the id so we can re-use this info later
                $refid=substr($result,$starttagpos+4,strpos($result,'</id>')-$starttagpos-4);
                //TEST moving ref into folder (check fl tag)
                $result = refworks_folder_api::add_ref_to_folder($refid,'UnitTestTemp');
                $this->assertNotEqual($result,false,'Add new ref to folder.[refworks_folder_api::add_ref_to_folder]');
                $result = refworks_ref_api::get_ref_data('&id='.$refid);
                $this->assertPattern("/UnitTestTemp/",$result,'Check ref in folder.');
                //Test removing ref from folder (check empty fl tag)
                $result = refworks_folder_api::remove_ref_from_folder($refid,'UnitTestTemp');
                $this->assertNotEqual($result,false,'Remove new ref from folder.[refworks_folder_api::remove_ref_from_folder]');
                //delete ref
                $result = refworks_folder_api::delete_folder_refs(array($refid));
                $this->assertNotEqual($result,false,'Delete ref.[refworks_folder_api::delete_folder_refs]');
            }
            //TEST rename folder (with funny name)
            $result = refworks_folder_api::rename_folder('UnitTestTemp','UnitTestTemp&2');
            $this->assertNotEqual($result,false,'Rename folder.[refworks_folder_api::rename_folder]');
            //TEST delete folder
            $result = refworks_folder_api::delete_folder_only('UnitTestTemp&2');
            $this->assertNotEqual($result,false,'Delete folder.[refworks_folder_api::delete_folder_only]');
            //TEST shared folders
            $result = refworks_folder_api::get_refshares();
            $this->assertIsA($result,'Array','Test getting refshares[refworks_folder_api::get_refshares]');
            //TEST that the UnitTest shared folder is active
            $foundsf = false;
            foreach (refworks_folder_api::$sharedfolders as $share) {
                if ($share['folder']=='UnitTest') {
                    $foundsf = true;
                }
            }
            $this->assertNotEqual($foundsf,false,'UnitTest share folder available.');
        }
    }

}

?>