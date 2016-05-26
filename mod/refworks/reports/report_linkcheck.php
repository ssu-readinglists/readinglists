<?php
/**
 * Report that will check links of given references and output a summary report
 *
 * @copyright &copy; 2010 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks/reports/
 */
require_once(dirname(__FILE__).'/../refworks_base.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/local/references/linking.php');;

class refworks_report_linkcheck extends refworks_report{

    //List of urls that are 'known' error pages (especially useful for sfx openurls as these default to a help page on error in OU)
    public static $errorpages = array('http://library.open.ac.uk/help/','http://library.open.ac.uk/help');

    public static function run_report($data) {
        //turn data string into an xml dom object
        if (!$refxml = self::string_to_dom($data)) {
            return;
        }

        $reflist = $refxml->getElementsByTagName('reference');

        //get all of the links
        $linkslist = linking::create_link($refxml);

        //check all of the links
        $checked = self::checklinks($linkslist);

        //now go through all of our links and check final destination for each url
        //We can have:
        //no (empty) link
        //OK link
        //HTTP error link
        //Link ends up at an error site - $errorpages (can't resolve to an article etc.)
        $oklinks = array();
        $httperrors = array();//array of all keys in $checked that have an http error
        $sfxerrors = array();//array of all keys in $checked that end up at an error site

        for ($a=0,$max=count($checked);$a<$max;$a++) {
            if ($checked[$a]->final!='') {
                if (in_array($checked[$a]->final,self::$errorpages)) {
                    $sfxerrors[] = $a;
                } else {
                    if ($checked[$a]->response!='' && $checked[$a]->response!=200) {
                        //if a 4xx or 5xx error code, flag as error
                        if ($checked[$a]->response > 400) {
                            $httperrors[] = $a;
                        } else {
                            //link must be OK
                            $oklinks[] = $a;
                        }
                    } else {
                        $oklinks[] = $a;
                    }
                }
            }
        }
        //print_object($checked);
        echo '<div class="report">';
        //show total number of refs
        echo '<div>'.get_string('report_linkcheck_totalrefs','refworks',$reflist->length).'</div>';

        //show total number of links generated
        echo '<div>'.get_string('report_linkcheck_totallinks','refworks',count($oklinks)+count($httperrors)+count($sfxerrors)).'</div>';

        //Show total number ok links
        echo '<div>'.get_string('report_linkcheck_totaloklinks','refworks',count($oklinks)).'</div>';

        //show total number of links 404 errors
        //Show what links errored (intial + final dest) + link to edit ref?
        echo '<div class="linklist">'.get_string('report_linkcheck_totalhttperrors','refworks',count($httperrors)).'</div>';
        self::write_list($httperrors,$checked,$reflist,'response');
        //Show total number of links that SFX could not resolve
        //Show what links (intial + final dest) + link to edit ref?
        echo '<div>'.get_string('report_linkcheck_totalerrorlinks','refworks',count($sfxerrors)).'</div>';
        self::write_list($sfxerrors,$checked,$reflist,'final');
        echo '</div>';
    }

    /**
     * writes a list of urls and link to assoc ref
     * @param $array array: array of array keys that are 'bad'
     * @param $checked array: array returned from checklinks
     * @param $reflist DOM: xml of references
     * @param $param string: 'final'=show any links that went to error pages, "response" show errors due to http response
     * @return unknown_type
     */
    private static function write_list($array, $checked, $reflist, $param) {
        global $CFG;

        echo '<ol>';
        foreach ($array as $key) {
            $refid = $reflist->item($key)->getElementsByTagName('id')->item(0)->nodeValue;
            $durl = '';
            //show a cut down url to user
            if (strlen($checked[$key]->final)<=100) {
                $durl = $checked[$key]->final;
            } else {
                $durl = substr($checked[$key]->final,0,97).'...';
            }
            $url = $checked[$key]->final;
            //if ($param == 'final') {
            $url = $checked[$key]->original;
            //}
            echo '<li><a href="'.$url.'" target="_blank">'.$durl.'</a> ';
            if ($param=='response') {
                echo '(<a href="http://en.wikipedia.org/wiki/List_of_HTTP_status_codes" target="_blank">'.$checked[$key]->response.'</a>) ';
            }
            //create edit form so you can view/update ref
            if (refworks_base::check_capabilities('mod/refworks:update')) {
                $output = '<form id="ref_updates_'.$refid.'" method="post" action="../managerefs.php" style="display:inline">';
                $output .= '<input type="image" name="update" src="'.refworks_display::get_image_paths()->edit.'" alt="'.get_string('ref_update','refworks').'" title="'.get_string('ref_update','refworks').' '.$refid.'" />';
                $output .= '<div style="display: none;">';
                $output .= '<input type="hidden" name="refid" value="'.$refid.'" />';
                $output .= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
                if (refworks_base::$isinstance) {
                    //if in instance also need id to be sent
                    $output .= '<input type="hidden" name="id" value="'.refworks_base::$cm->id.'" />';
                }
                $output .= '</div></form>';
                echo $output;
            }
            echo '</li>';
        }
        echo '</ol>';
    }

    /**
     * Checks each of the url's sent using multi-curl
     * Can check for sfx links where the sfx page is an iframe
     * @param $linkarray array: urls to check
     * @return array: array of objects with ->final(end url) & ->response(http code) & ->original (initial link)
     */
    public static function checklinks($linkarray) {
        $returndata = array();
        $curls = array();
        $cm = curl_multi_init();

        //get cur cookie info
        $cookie = '';
        /*foreach ($_COOKIE as $cook=>$cookval) {
            $cookie .= $cook.'='.$cookval.'; ';
        }*/

        $sfxapi = false;
        //create curl object for each link (null if not link)
        foreach ($linkarray as $link) {
            $url = $link["url"];
            if ($url == null || $url=='') {
                $curls[] = null;
            } else {
                //check for sfx:
                //could replace this with a check for link type
                if (stripos($url,linking::$openurlprefix)!==false && stripos($url,'sfx')!=false) {
                    //Add URL into array will be checked later to create an api call which will populate a curl obj in its place
                    $curls[] = $url;
                } else {
                    $curl = self::create_curl($url, $cookie);
                    curl_multi_add_handle($cm, $curl);
                    $curls[] = $curl;
                }
            }
        }
        //before running the curl look for sfx urls and tranform into an api call
        //result will populate the array elements with actual/final link
        self::add_url_from_sfx_api($curls, $cm);
        //run curl multi + wait
        $running=null;
        $t1 = time();
        //execute the handles
        do {
            curl_multi_exec($cm,$running);
            usleep(100000);//need this here to stop timeout
            if (time() > $t1 + 20)
            {
                //write to the output; stops browser timing out
                echo " ";
                $t1 = time();
            }

        } while ($running > 0);

        $counter = 0;
        //run through each curl result
        foreach ($curls as $result) {
            $retobj = new stdClass();
            $retobj->final = '';
            $retobj->response = '';
            $retobj->original = $linkarray[$counter];
            //if not a curl object (null) do something
            if ($result!=null) {
                //if not a curl object assume final destination
                if (is_string($result)) {
                    $retobj->final = $result;
                    $retobj->response = 200;
                } else {
                    //it was a curl object, where did it go?
                    $retobj->final = curl_getinfo($result,CURLINFO_EFFECTIVE_URL);
                    $retobj->response = curl_getinfo($result,CURLINFO_HTTP_CODE);
                    curl_multi_remove_handle($cm, $result);
                }

            }
            $retdata[] = $retobj;
            $counter++;
        }

        curl_multi_close($cm);

        return $retdata;
    }

    /**
     * Create a curl object
     * @param $url string: url you want to get
     * @param $cookie string: cookie string
     * @return curl object
     */
    private static function create_curl($url, $cookie='',$post='') {
        $c = curl_init($url);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION  ,true);
        curl_setopt($c, CURLOPT_HEADER, false);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 5);
        //curl_setopt($c, CURLOPT_TIMEOUT, 30);

        curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($c, CURLOPT_HTTPHEADER, array("Expect:"));

        //proxy?
        global $CFG;
        if (isset($CFG->proxyhost)) {
            curl_setopt($c, CURLOPT_PROXY, $CFG->proxyhost);
            if (isset($CFG->proxyport)) {
                curl_setopt($c, CURLOPT_PROXYPORT, $CFG->proxyport);
            }
            curl_setopt($c, CURLOPT_HTTPPROXYTUNNEL, 0);
        }
        //curl_setopt($c, CURLOPT_COOKIE, $cookie);

        if ($post!='') {//sfx api call
            curl_setopt($c, CURLOPT_POST, true);
            curl_setopt($c, CURLOPT_POSTFIELDS, array('url_ctx_val'=>$post,'url_ver'=>'Z39.88-2004','url_ctx_val_nose'=>'yes','url_ctx_fmt'=>'info:ofi/fmt:xml:xsd:ctx','sfx.response_type'=>'multi_obj_xml'));
        }

        return $c;
    }

    //
    /**
    * looks at array for urls (assumed openurl) and adds to an sfx api call
    * results(destination urls) overwrite original elements with curl objects and added to $cm
    * @param $curls
    * @param $cm
    * @return unknown_type
    */
    private static function add_url_from_sfx_api($curls, $cm) {
        //concerns over sfx api handling lots of openurls per call, split into blocks of 25
        $chunks = array_chunk($curls,25,true);
        $curlobjs = array();
        $cm2 = curl_multi_init();

        foreach ($chunks as $chunk) {
            $xmltopost ='<?xml version="1.0" encoding="UTF-8" ?><ctx:context-objects xmlns:ctx="info:ofi/fmt:xml:xsd:ctx" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="info:ofi/fmt:xml:xsd:ctx http://www.openurl.info/registry/docs/info:ofi/fmt:xml:xsd:ctx">';
            //get array keys, then look in original array for element
            $chunkkeys=array_keys($chunk);
            foreach ($chunkkeys as $key) {
                if ($curls[$key]!='' && is_string($curls[$key])) {
                    $xmltopost.= self::create_openurl_xml($curls[$key],$key+1);
                }
            }

            $xmltopost .= '</ctx:context-objects>';

            $curlobj = self::create_curl('http://openurl.open.ac.uk:3210/sfxlcl3?sfx.response_type=multi_obj_xml','',$xmltopost);
            $curlobjs[]=$curlobj;
            curl_multi_add_handle($cm2, $curlobj);
        }
        $running=null;
        $t1 = time();
        //execute the handles
        do {
            curl_multi_exec($cm2,$running);
            usleep(100000);//need this here to stop timeout
            if (time() > $t1 + 20)
            {
                //write to the output; stops browser timing out
                echo " ";
                $t1 = time();
            }

        } while ($running > 0);
        $page='';
        foreach ($curlobjs as $curlobj) {
            $page.=curl_multi_getcontent($curlobj);
            curl_close($curlobj);
        }
        curl_multi_close($cm2);
        //SFX API returns xml, with <ctx_obj_set><ctx_obj identifier="2"><target_url>
        if (count($chunks)>1) {
            //strip out top level xml stuff in case more than one call from multi_curl obj
            $page=str_replace("<ctx_obj_set>",'',$page);
            $page=str_replace("</ctx_obj_set>",'',$page);
            $page=str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>",'',$page);
            //add in single top level xml stuff
            $page="<?xml version=\"1.0\" encoding=\"UTF-8\"?><ctx_obj_set>".$page.'</ctx_obj_set>';
        }
        //check converts to dom
        if (!$sfxxml = self::string_to_dom($page)) {
            return;
        }
        //get each context object ctx_obj
        $contextobjs = $sfxxml->getElementsByTagName('ctx_obj');
        //loop through and see if has target_url tag if so take 1 off identifier and make a curl obj with url (if not error)
        foreach ($contextobjs as $response) {
            $linktags = $response->getElementsByTagName('target_url');
            if ($linktags->length>0) {
                $url = $linktags->item(0)->nodeValue;
                $indentifier = $response->getAttribute('identifier')-1;
                if (in_array($url,self::$errorpages)) {
                    //only write in an error page if not a curl obj already (sometimes multiple context obj for same ref are returned)
                    if (is_string($curls[$indentifier])) {
                        $curls[$indentifier] = $url;
                    }
                } else {
                    $curls[$indentifier] = self::create_curl($url, '');
                    curl_multi_add_handle($cm, $curls[$indentifier]);
                }
            }
        }
    }

    /**
     * Turns an openurl into sfx api call xml
     * @param $openurl string: openurl
     * @param $id int: the identifier passed into the call
     * @return string(xml)
     */
    private static function create_openurl_xml($openurl,$id) {
        //first work out if journal or book
        $type = 'book';//default is book as handles more types
        if (stripos($openurl,'rft.genre=article') || stripos($openurl,'rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal')) {
            $type= 'journal';
        }
        $identifier='';
        $ignore='';
        $time = date(DATE_W3C);
        //start of object
        $xml =<<<XML
<ctx:context-object timestamp="$time" version="Z39.88-2004" identifier="$id">
<ctx:referent>
<ctx:metadata-by-val>
<ctx:format>info:ofi/fmt:xml:xsd:$type</ctx:format>
<ctx:metadata>
<rft:$type xmlns:rft="info:ofi/fmt:xml:xsd:$type" xsi:schemaLocation="info:ofi/fmt:xml:xsd:$type http://www.openurl.info/registry/docs/info:ofi/fmt:xml:xsd:$type">
XML;

        //now fill with referent info (rft:)
        $params = array();
        $params = parse_url($openurl);
        if (substr_count($params['query'],'rft_id')>=2) {
            //in some special cases we can have more than 1 rft_id in the url, need to rename 1 so both are kept
            $params['query']=substr_replace($params['query'],'rft_id1',strripos ($params['query'],'rft_id'),6);
        }
        parse_str($params['query'], $params);
        foreach ($params as $param=>$paramval) {
            if (stripos($param,'rft_')!==false && stripos($param,'rft_val_fmt')===false && stripos($param,'rft_id')===false) {
                $param = str_replace('rft_','rft:',$param);
                $param = str_replace('amp;','',$param);
                $param = str_replace('&','',$param);
                $xml .= "<$param>".urldecode(htmlspecialchars($paramval,ENT_NOQUOTES,'utf-8',false))."</$param>";
            }else if (stripos($param,'rft_id')!==false) {
                //special case for referent id as this must be in a context tag
                //this can be either a DOI or URL, if already set url takes preference
                if ($identifier == '' || strpos($paramval,'://')) {
                    $identifier = '<ctx:identifier>'.urldecode(htmlspecialchars($paramval,ENT_NOQUOTES,'utf-8',false)).'</ctx:identifier>';
                }
            }else if (stripos($param,'sfx_ignore_date_threshold')!==false) {
                $ignore='<sfx><metadata-by-val><metadata><ignore_date_threshold>1</ignore_date_threshold></metadata></metadata-by-val></sfx>';
            }
        }

        //end tags
        $xml.=<<<XML
</rft:$type>
</ctx:metadata>
</ctx:metadata-by-val>$identifier
</ctx:referent>
<ctx:requester>
</ctx:requester>
<ctx:service-type>
<ctx:metadata-by-val>
<ctx:format>info:ofi/fmt:xml:xsd:sch_svc</ctx:format>
<ctx:metadata>
<sv:fulltext xmlns:sv="info:ofi/fmt:xml:xsd:sch_svc">yes</sv:fulltext>
</ctx:metadata>
</ctx:metadata-by-val>
</ctx:service-type>
<ctx:referrer>
<identifier>info:sid/learn.open.ac.uk:MYREFSREPORT</identifier>
</ctx:referrer>$ignore
</ctx:context-object>
XML;
        return $xml;
    }
}

?>