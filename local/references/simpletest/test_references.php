<?php
/**
 * Unit [simple] Test class for the references package.
 * Tests Connection to RefWorks and reference data conversions
 *
 * @copyright &copy; 2009 The Open University
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

require_once(dirname(__FILE__).'/../rwapi/rwapi.php');
require_once(dirname(__FILE__).'/../convert/refxml.php');
require_once(dirname(__FILE__).'/../apibib/apibib_lib.php');

//hacky class so that we can get to protected methods
class call_rwapi extends rwapi{
    public static function call_api($a,$b) {
        return parent::call_api($a,$b);
    }
}

// Include the code to test
/** This class contains the test cases for the functions in editlib.php. */
class references_test extends UnitTestCase {

    protected $refxml; //refxml instance

    public function setUp() {
        $refxml=new refxml();
        $this->assertIsA($refxml,'refxml');
        if ($refxml instanceof refxml) {
            $this->refxml = $refxml;
        }
    }
    public function tearDown() {
        $this->refxml=NULL;
    }

    function test_rwapi() {

        //Test rwapi - connection to refworks api (use a call that doesn't need a valid session first)
        $result = call_rwapi::call_api('authentication','newtempusersess');
        $this->assertNotIdentical($result,false,'Connecting to refworks api.');
    }

    //Test APIBIB service - This also tests the api (rwapi)
    function test_apibib() {
        global $CFG;

        //Test rwapi - connection to 'code' account that is used by apibib etc
        $result = rwapi::check_session('','VLE_account','VLEaccPa$$word');
        $this->assertNotIdentical($result,false,'Connecting to special Moodle account in refworks institution db.');
        if ($result) {
            rwapi::destroy_session();
        }

        $refstring=<<<XMLSTRING
<?xml version="1.0" encoding="utf-8"?>
<refworks xmlns:refworks="www.refworks.com/xml/">
<reference>
<rt>Book, Whole</rt>
<sr> Print(0)</sr><id>19</id>
<a1>Chambers,Ellie</a1>
<a1>Northedge,Andrew</a1>
<a1>Open University</a1>
<t1>The arts good study guide</t1>
<yr>1997</yr>
<k1>Study, Method of</k1>
<k1>Arts</k1>
<k1>Study and teaching (Higher)</k1>
<no>blah</no>
<pb>Open University</pb>
<pp>Milton Keynes</pp>
<sn>0749287454</sn>
<cn>371.30281 CHA</cn>
<ol>Unknown(0)</ol></reference>
</refworks>
XMLSTRING;
        $referencestyles = apibib::get_referencestyles();

        $result = apibib::getbib($refstring,$referencestyles[0]['string'],'RefWorks XML');

        $this->assertNotIdentical($result,false,'Use APIbib, style:'.$referencestyles[0]['string']);
        $thorough=optional_param('thorough', false, PARAM_BOOL);//check if user selected a comprehensive test
         if ($result!==false && $thorough) {
            //Now do the test again with all the other styles
            //this makes the test slower, but more complete
            for ($a=1,$max=count($referencestyles);$a<$max;$a++) {
                 $result = apibib::getbib($refstring,$referencestyles[$a]['string'],'RefWorks XML');

                 $this->assertNotIdentical($result,false,'Use APIbib, style:'.$referencestyles[$a]['string']);
            }
        }
        if ($result) {
            rwapi::destroy_session();
        }
    }

    //test conversion functions in refxml
    function test_refxml_functions() {
        global $CFG;
        //string of test ris file
        $refstring=<<<RISSTRING
TY  - BOOK
ID  - 19
A1  - Chambers,Ellie
A1  - Northedge,Andrew
A1  - Open University A103/Set books
T1  - The arts good study guide
Y1  - 1997
KW  - Study, Method of
KW  - Arts
N1  - Ellie Chambers and Andrew Northedge.
PB  - Open University
CY  - Milton Keynes
A3  - Anonymous
SN  - 0749287454
N1  - 371.30281 CHA
M1  - Book, Whole
ER  -
RISSTRING;

        $datatype=$this->refxml->return_data_type($refstring);
        //TEST THAT THE LIBRARY THINKS THIS IS A RIS FILE
        $this->assertEqual($datatype,'RIS');
        $result=$this->refxml->return_transform_in($refstring,'RIS');
        //TEST that the transform to refxml result did not fail
        $this->assertNotIdentical($result,true,'Transform to ref xml');
        //TEST that the library thinks the result (as a string) is a refxml file
        $datatype=$this->refxml->return_data_type($result->saveXML());
        $this->assertEqual($datatype,'RefWorksXML');

        //data manipulation tests
        //Test data sorting (returns false if error)
        $test=$this->refxml->sort_references($result,'title');
        $this->assertEqual($test,true,'references xml sorting');
        //Test return_reference by id (return as nodelist)
        $test=$this->refxml->return_reference($result,array('19'));
        $this->assertIsA($test,'DOMNodeList');
        //TEST the id of the node returned
        $correct=false;
        foreach ($test as $node) {
            if ($node->getAttribute('id')=='19') {
                $correct=true;
            }
        }
        $this->assertEqual($correct,true,'references xml return_reference id check');
        //TEST return_reference by id (return as array)
        $test=$this->refxml->return_reference($result,array('19'),true,true);
        $this->assertEqual(count($test),1,'references xml return_reference id check (array)');

        //TEST return_references
        $test=$this->refxml->return_references($result,true,'nowt');
        $this->assertPattern("/xmlns:refworks/",$test);

        //TEST conversion to RIS
        $opts=new stdClass;
        $opts->filename='';
        $opts->unittest=true;
        $test=$this->refxml->transform_out($result,'RIS',$opts);
        $this->assertNotEqual($test,false,'Export conversion to RIS');
        //test result against ris data type test
        $test2=$this->refxml->test_data_type($test->contents,'RIS');
        $this->assertEqual($test2,true,'Test conversion to RIS is RIS format');

        //TEST conversion to refworks xml
        $test=$this->refxml->transform_out($result,'RefWorksXML',NULL);
        $this->assertNotEqual($test,false,'Export conversion to RefWorks XML');
        //test result against ris data type test
        $test2=$this->refxml->test_data_type($test->contents,'RefWorksXML');
        $this->assertEqual($test2,true,'Test conversion to RefWorks xml is right format');

        //TEST Ref works export (direct export). Doesn't actually try to add to refworks
        $test=$this->refxml->transform_out($result,'RefWorksDE',$opts);
        $this->assertNotEqual($test,false,'Export conversion to RefWorks Direct Export');
        //test refworks page is online (using snoopy as already use this for rss test)
        $page = download_file_content('http://www.refworks.com/refworks/autologin.asp', null, null, true);

        $this->assertEqual(strlen($page->error),0,$page->error);


        //TEST conversion to ref_filter
        //$test=$this->refxml->transform_out($result,'MoodleFilter',$opts);
        //$this->assertPattern("/&lt;reference/",$test,'Check moodle filter export');
    }

    //test can access refshare rss feed and convert using refxml (as used in resourcepage)
    function test_refshare_rss_import() {
        global $CFG;
        //try and connect to feed - this is a personal folder feed so may be deleted in future
        $page = download_file_content('http://www.refworks.com/refshare/?site=015791142406000000/RWWS6A817113/UnitTest&rss', null, null, true);

        $this->assertEqual(strlen($page->error),0,$page->error);

        if (strlen($page->error)==0) {
            //TEST xml has been return (i.e. not error html)
            $xmlpos=strpos($page->results,'<?xml');
            $this->assertIdentical($xmlpos,0,'Test that refshare folder is available');
            if ($xmlpos==0) {
                //if feed has loaded ok - TEST it transforms
                $result=$this->refxml->return_transform_in($page->results,'RefWorksRSS','date');
                $this->assertNotEqual($result,false,'RSS transform through xslt to ref xml');
            }
        }
    }
}

?>