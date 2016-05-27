<?php
/**
 * Retrieve a formatted set of references in a RefShare feed in specified style
 *
 * @author owen@ostephens.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package filter
 * @subpackage refshares
 */

/*
 * TO DO
 * Need to refactor - e.g. see code duplication between cached_refshare and cache_refshare functions
 * 					Probably need to make more assumptions, or separate out functions?
 * Think about putting default reference style and sort order into options?
*/

function cached_refshare($refshare_url, $refshare_style) {
	global $CFG, $DB;
		
	// Using cache_filters table to store cached version of styled references
	$table = 'cache_filters';
	
	// Check style exists, and if not set to default
	$style = check_style($refshare_style);
	
	// Need entries to be unique for each RefShare feed/Style combo
	$md5key = md5($refshare_url.$style);

	// first check if refshare is cached in the table at all
	if ($exists = $DB->get_record($table,array('filter' => 'refshares','md5key' => $md5key))) {
		// Record exists, need to check if cache is valid, otherwise refresh?
		if (time()-$exists->timemodified < $CFG->filter_refshares_cacheexpires) {
			// Cache not expired, so can return directly
			error_log("Using cached list");
			$formatted_refs = stripslashes(htmlspecialchars_decode($exists->rawtext));
		} else {
			// Need to update refshare
			$formatted_refs = update_cached_refshare($refshare_url, $style);
		}
		return $formatted_refs;
	} else {
		// Record does not exist, needs creating for first time - call 'cache_refshare' function
		// Will be slow....
		cache_refshare($refshare_url, $style);
		// Now check if exists, if not then return an error
		if ($exists = $DB->get_record($table,array('filter' => 'refshares','md5key' => $md5key))) {
			$formatted_refs = stripslashes(htmlspecialchars_decode($exists->rawtext));
			return $formatted_refs;
		}
		else {
			error_log('Could not retrieve cached references for '.$refshare_url.'#'.$style.', even after trying to create');
			return false;
		}
	}
}

function cache_refshare($refshare_url, $refshare_style) {
	global $DB;
	// Using cache_filters table to store cached version of styled references
	$table = 'cache_filters';
	
	// Check style exists, and if not set to default
	$style = check_style($refshare_style);

	// Need entries to be unique for each RefShare feed/Style combo
	$md5key = md5($refshare_url.$style);

	// Create cache of refshare rss feed in specified style for the first time
	if ($exists = $DB->get_record($table,array('filter' => 'refshares','md5key' => $md5key))) {
		// Already a record
		$formatted_refs = stripslashes(htmlspecialchars_decode($exists->rawtext));
	} elseif($formatted_refs = format_refshare($refshare_url, $style)) {		
		// Doesn't exist, going to create
		$newrec = new stdClass();
		$newrec->filter = 'refshares';	
		$newrec->md5key = $md5key;
		$newrec->version = 1;
		$newrec->rawtext = addslashes(htmlspecialchars($formatted_refs));
        $newrec->timemodified = time();
		$DB->insert_record($table, $newrec);
	} else {
		$debug = 'Unable to create cache';
		error_log($debug);
		return false;
	}
	
	return $formatted_refs;
}

function update_cached_refshare($refshare_url, $refshare_style) {
	global $DB;
	$table = 'cache_filters';
	
	// Check style exists, and if not set to default
	$style = check_style($refshare_style);

	// Need entries to be unique for each RefShare feed/Style combo
	$md5key = md5($refshare_url.$style);

	// Check the record exists
	if ($exists = $DB->get_record($table,array('filter' => 'refshares','md5key' => $md5key))) {
		// Only want to go ahead if we can get updated version
		// Otherwise write an error
		if($formatted_refs = format_refshare($refshare_url, $style)) {
			$updaterec = new stdClass();
			$updaterec->id = $exists->id;
			$updaterec->rawtext = addslashes(htmlspecialchars($formatted_refs));
	        $updaterec->timemodified = time();
			$DB->update_record($table, $updaterec);
		} else {
			$debug = 'Unable to update cache, using existing cache for '.$refshare_url.'#'.$style.'. See debugging for more details';
			error_log($debug);
			$formatted_refs = stripslashes(htmlspecialchars_decode($exists->rawtext));
		}
	} else {
		// Doesn't exist, check if we can create
		if($formatted_refs = format_refshare($refshare_url, $style)) {
			$newrec = new stdClass();
			$newrec->filter = 'refshares';	
			$newrec->md5key = $md5key;
			$newrec->version = 1;
			$newrec->rawtext = addslashes(htmlspecialchars($formatted_refs));
	        $newrec->timemodified = time();
			$DB->insert_record($table, $newrec);
		} else {
			$debug = 'Unable to retrieve styled RefShare for '.$refshare_url.'#'.$style.', and there is no cached version. See debugging for more details';
			error_log($debug);
			// What should we return here?
			return false;
		}
	}
	return $formatted_refs;
}

function format_refshare($refshare_url, $refshare_style) {
	
	global $CFG;
	require_once($CFG->dirroot.'/local/references/convert/refxml.php');
	require_once($CFG->dirroot.'/local/references/apibib/apibib_lib.php');
	require_once($CFG->dirroot.'/local/references/linking.php');
	// Copy RefShare URL so we have the original still after we have modified where necessary
	$refshare_full_url = $refshare_url;
	// Modify RefShare URL to retrieve all items in a feed
	if(strpos($refshare_full_url,'&rss=true')===false){
        $refshare_full_url=str_replace('&rss','&rss=true',$refshare_full_url);
    }
	if(strpos($refshare_full_url,'&basedate=01011900')===false){
		$refshare_full_url.='&basedate=01011900';
	}
    
	// Retrieve feed using curl	
	$c = curl_init($refshare_full_url);
    curl_setopt($c, CURLOPT_HTTPGET, true);

    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    if(array_key_exists('HTTP_USER_AGENT',$_SERVER)){
    	curl_setopt($c, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    }
    curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 15);
	// If '/' is omitted between 'http://www.refworks.com/refshare' and '?' then there is a redirect to the correct URL, so enable CURL to follow redirects
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($c, CURLOPT_MAXREDIRS, 1);

    //manual header
    $header[] = "Content-type: text/xml";
    curl_setopt($c, CURLOPT_HTTPHEADER, $header);

    //set proxy details, use either values sent to method, or moodle values
    if(isset($CFG->proxyhost)){
        //setup  class with proxy that moodle is using
        if($CFG->proxyhost!=''){
            curl_setopt($c, CURLOPT_PROXY, $CFG->proxyhost);
            if($CFG->proxyport!=''){
                curl_setopt($c, CURLOPT_PROXYPORT, $CFG->proxyport);
            }
        }
    }
    curl_setopt($c, CURLOPT_HTTPPROXYTUNNEL, false);
    $returnedarray = array(); // for results
    $page = curl_exec($c);
	$debug = 'Just for logging. URL requested was '.$refshare_full_url.'<br />';
	$debug .= 'Last http code was: '.curl_getinfo($c,CURLINFO_HTTP_CODE).'<br />';
	$debug .= 'Last http url was: '.curl_getinfo($c,CURLINFO_EFFECTIVE_URL).'<br />';
	$debug .= 'Number of redirects was: '.curl_getinfo($c,CURLINFO_REDIRECT_COUNT);	
	error_log($debug);
		
    if ($page == false) {
		$debug = 'Cannot parse RefWorks RSS feed (cannot load into DOM). Results from fetching feed were: ';
		$debug .= curl_getinfo($c,CURLINFO_HTTP_CODE);
		error_log($debug);
		return false;
    }else{
		if(strpos($page,'<?xml')===false){
			$debug = 'Cannot parse RefWorks RSS feed (does not seem to be xml). URL requested was '.$refshare_full_url.'<br />';
			$debug .= 'Last http code was: '.curl_getinfo($c,CURLINFO_HTTP_CODE).'<br />';
			$debug .= 'Last http url was: '.curl_getinfo($c,CURLINFO_EFFECTIVE_URL);
			error_log($debug);
			return false;
        }
    }

    curl_close ($c);

	//instance of ref xml management class
	$refman=new refxml();

	//load feed into dom + check
	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = false;

	if(!$dom->loadXML($page)){
		$debug = 'Cannot parse RefWorks RSS feed (cannot load into DOM). Results from fetching feed were';
		error_log($debug);
		return false;
	}

	if($refman->test_data_type($dom,'RefWorksRSS')===false){
		$debug = 'Cannot parse RSS feed (Does not seem to be a RefWorks RSS)';
		error_log($debug);
		return false;
	}
	
	//get title (of RSS feed) + items
	$title = $dom->getElementsByTagName('title')->item(0)->nodeValue;
	//Step 1 - 
	//Convert rss feed into a reference data xml (RefWorks XML)
	//First check if we can convert the data from rss xml to a standard format
	$refxml=false;
	//when converting rss, sort by author
	//perhaps this should go into a filter option?
	$refxml=$refman->return_transform_in($dom,'RefWorksRSS','creator');
	//clear initial data vars, as no longer needed.
	unset($dom);
	unset($xpath);
	if($refxml===false) {
		$debug = 'Error processing RefWorks RSS feed';
		error_log($debug);
		return false;
	}
		
	// Step 2 - 
	// Make sure style exists, and if not use default
	$style = check_style($refshare_style);

	$refstring=$refman->return_references($refxml,true);
	$titles=apibib::getbib($refstring, $style,'RefWorks XML');
	if(!$titles){
		$debug = 'Error retrieving styled references from RefWorks';
		error_log($debug);
		return false;
	}
	if(!is_array($titles)){
		$titles=array();
	}

	//Step 3 - get data for each item
	$alldata=$refman->return_reference($refxml,array(),true,true);
	if($alldata===false){
		$alldata=array();
	}

	//Step 4 - 
	//Work out weblink for each item
	//work out if we need to send the course name to the link as this is used to track where links come from when using OpenURL
	$coursename = '';
	/*
	########
	######## Not currently including course context as filter is cached and could be re-used in any course context
	######## Therefore including course context doesn't make sense
	######## Also Cron does not have a course context for a filter
	########
	if(isset($COURSE->id) && $COURSE->id == SITEID) {
		//in cron job - don't know course id, so get from db using $modid
		if($modid>0){
			if($courseid=get_field('resourcepage','course','id',$modid)){
				$coursename = get_field('course','shortname','id',$courseid);
			}
		}
	}
	*/

	//Step 5 - 
	//Create html containing styled and linked references
	//The span title is used by the cron job to know which refshare to refresh when retrieving details from the cache_filters table
	//therefor we use the original RefShare URL here
	$formatted_refs = '<span class="refshare" title="'.$refshare_url.'#'.$refshare_style.'">';
	$count = count($alldata);
	for ($i=0;$i<$count;$i++){
		$data = $alldata[$i];
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		if(!$dom->loadXML($data)){
			$debug = 'Cannot parse create styled references with link (cannot load data into DOM)';
			error_log($debug);
			return false;
		}
		$linking = linking::create_link($dom, $coursename); //create weblink
		$title = $titles[$i];
		$desc = '';
		$notes = $dom->getElementsByTagName('no');
		if($notes->length >0){
			$noteval = $notes->item(0)->nodeValue;
			$desc = clean_text($noteval, FORMAT_HTML).'<br>';
		}
		
		$weblink=$linking[0]["url"];
		
		$formatted_refs .= '<span class="reference">';
		if (isset($weblink)) {
			$formatted_refs .= '<a href="'.$weblink.'"';
			if ($CFG->filter_refshares_linkbehaviour === 'newwin') {
				$formatted_refs .= ' target="ReferenceLink"';
			}
			$formatted_refs .= '>'.$title.'</a>';
		} else {
			$formatted_refs .= $title;
		}
		$formatted_refs .= '</span><br><span class="reference_note">'.$desc.'</span><br>';
		unset($dom);
	}
	
	$formatted_refs .= '</span>';

	// Going to return the html we are looking for....
	return $formatted_refs;
	
}

function check_style($refshare_style) {
	// Check given style exists in $referencestyles array specified in local/references/apibib_lib.php
	// Convert to lower case before checking to be forgiving
	// Otherwise use the first one...
	global $CFG;
	require_once($CFG->dirroot.'/local/references/apibib/apibib_lib.php');
	$style = '';
	$stylearray = apibib::get_referencestyles();
	foreach($stylearray as $referencestyle) {
		if (strtolower($refshare_style) === strtolower($referencestyle['string'])) {
			$style = $referencestyle['string'];
			break;
		}
	}
	if(!$refshare_style=$style) {
		$style = $stylearray[0]['string'];
		error_log("Requested Style: ".$refshare_style." is not defined in style array, so using ".$style." instead.");
	}
	
	return $style;
	
}

function expire_cache($refshare_url, $refshare_style) {
	global $CFG, $DB;
		
	// Using cache_filters table to store cached version of styled references
	$table = 'cache_filters';
	
	// Check style exists, and if not set to default
	$style = check_style($refshare_style);
	
	// Need entries to be unique for each RefShare feed/Style combo
	$md5key = md5($refshare_url.$style);

	// first check if refshare is cached in the table at all
	if ($exists = $DB->get_record($table,array('filter' => 'refshares','md5key' => $md5key))) {
		// Record exists, need to expire
		$updaterec = new stdClass();
		$updaterec->id = $exists->id;
		$updaterec->rawtext = $exists->rawtext;
        $updaterec->timemodified = $exists->timemodified - $CFG->filter_refshares_cacheexpires;
		$DB->update_record($table, $updaterec);
		error_log("Expired cached version");
	} else {
		error_log("No cached version found, cannot expire");
	}
	return;
}


?>