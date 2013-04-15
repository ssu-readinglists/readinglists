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

require_once(dirname(__FILE__).'/../getdata.php');
require_once(dirname(__FILE__).'/../linking.php');

// Include the code to test

class references_supportlibs_test extends UnitTestCase {


    public function setUp() {
    }
    public function tearDown() {
    }

    //Test getdata
    function test_getdata() {
        global $CFG;

        //Test connection to crossref
        $result = references_getdata::call_crossref_api('10.1037/0003-066X.59.1.29');
        $this->assertEqual($result,true,'Checking CrossRef API');
        //Test connect to worldcat
        $result = references_getdata::call_worldcat_api('9780596101015');
        $this->assertEqual($result,true,'Checking WorldCat API');
    }

    //test linking
    function test_linking() {
        $refstring=<<<XMLSTRING
<?xml version="1.0" encoding="utf-8"?>
<refworks xmlns:refworks="www.refworks.com/xml/">
<reference>
<rt>Book, Whole</rt>
<id>19</id>
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
</reference>
<reference>
<rt>Book, Whole</rt>
<id>19</id>
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
<ul>http://www.open.ac.uk</ul>
</reference>
<reference>
<rt>Book, Whole</rt>
<id>19</id>
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
<u5>http://www.open.ac.uk</u5>
</reference>
</refworks>
XMLSTRING;

        $data = new DOMDocument('1.0','utf-8');
        $data->loadXML($refstring);
        $result = linking::create_link($data,'TEST');
        $this->assertEqual($result[0]["url"],null,'Linking: Test book creates no link');
        $this->assertPattern("/www.open.ac.uk/",$result[1]["url"],'Linking: Test ul link');
        $this->assertPattern("/TEST/",$result[1]["url"],'Linking: Test course gets passed');
        $this->assertEqual($result[2]["url"],'http://www.open.ac.uk','Linking: Test u5 field override');

        //if thorough then check url from linking is valid
        $thorough=optional_param('thorough', false, PARAM_BOOL);//check if user selected a comprehensive test
        if ($thorough) {
            $page = download_file_content($result[1], null, null, true);
            $this->assertEqual(strlen($page->error),0,$page->error);
        }
    }

}

?>