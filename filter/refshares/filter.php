<?php
/**
 * Filter to display a formatted list of references given a RefShare RSS Feed address
 *
 * @author owen@ostephens.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package filter
 * @subpackage refshares
 */
class filter_refshares extends moodle_text_filter {
	public function filter ($text, array $options = array()) {
	/*
	* To do:
	* Consider having a filter option as a refshare base url so only code is needed?
	*
	* Filter for pattern of [refshare url]#[style]
	* e.g. http://www.refworks.com/refshare/?site=015791142406000000/RWWS3A1351696/Telstar&amp;rss#harvard
	*/

#    	global $CFG, $COURSE;

    	// Do some initial checks to make sure we aren't doing unnecessary work... 
    	if(empty($text)){
        	return $text;
    	}
	
		if (!is_string($text)) {
        	// non string data can not be filtered anyway
        	return $text;
    	}

		// Checking for existence of RefShare URL
		// Form is http://www.refworks.com/refshare/?site=015791142406000000/RWWS3A1351696/Telstar&amp;rss
    	if(strpos($text,'refshare')===false){
        	//no refshare tag detected so return
        	return $text;
    	}
	
		// RefShare is in the text, so we are going ahead with the filter
		//Only require libs when we are sure we need them.
    	include_once(dirname(__FILE__).'/format_refshare.php');
	
		// Make a copy so we have the original should we need it
		$newtext = $text;

		// Copying form of mediaplugin filter
		// Define search string - assumes code followed by whitespace or a html entity starting <
    	$search = '/(http:\/\/www\.refworks\.com\/refshare[\/\?][^\s<]*)/is';
    	$newtext = preg_replace_callback($search, 'refshares_plugin_callback', $newtext);

		return $newtext;
	}
}

	function refshares_plugin_callback($refshares_url_style) {
		global $CFG, $COURSE, $PAGE;
		// $refshares_url_style is an array of matches from the text string
		// $refshares_url_style[0] and $refshares_url_style[1] are equivalent in this case
		$refshare_param = explode('#',$refshares_url_style[1]);
		//Might want to be a bit cleverer here - all special characters will have been encoded so unencode?
		$refshare_rss = preg_replace('/&amp;/','&',$refshare_param[0]);
		$refshare_style = $refshare_param[1];
		
		$pattern = '/site=(.*?)\/(.*?)\/(.*?)&(amp;)?rss/';
		preg_match($pattern,$refshare_rss,$matches);
		
		$refshare_url_encoded = urlencode("http://www.refworks.com/refshare?site=").
								$matches[1]."%2F".$matches[2]."%2F".urlencode($matches[3]).
								"%26rss";
		$refshare_url = urldecode($refshare_url_encoded);
		
		if($CFG->filter_refshares_usejs=='no') {
			$refshare_formatted = cached_refshare($refshare_url, $refshare_style);
			if (!$refshare_formatted){
				$refshare_url = preg_replace('/&rss/','',$refshare_rss);
				return 	get_string('usererrormsg','filter_refshares').'<a href="'.$refshare_url.'">'.$refshare_url.'</a>.';
			}
			return $refshare_formatted;
		} else {
			$refshare_key = $matches[1]."-".$matches[2]."-".$matches[3]."-".$refshare_style;
			// Create div ID that will be used to display list
			// First remove any characters that are invalid in html ids
			$refshare_key = preg_replace('/[^a-zA-Z0-9-_]/','',$refshare_key);
			// Make sure it starts with a letter
			$refshare_div = "refshare-".$refshare_key;

			$load_icon_url = $CFG->wwwroot."/filter/refshares/loading.gif";
			$loading_msg = get_string('userloadingmsg','filter_refshares').'<img src="'.$load_icon_url.'">';
			$error_msg = get_string('usererrormsg','filter_refshares');
			$javascriptoff_msg = get_string('javascriptoff','filter_refshares');
			
			// Initialise javascript with appropriate module and parameters
			$jsmodule = array(
								'name'  =>  'filter_refshares',
								'fullpath'  =>  '/filter/refshares/module.js',
								'requires'  =>  array('base', 'node', 'json', 'io', 'dump')
						);

			$jsdata = array($refshare_url_encoded,
		            		$refshare_style,
							$refshare_div,
							$CFG->wwwroot,
							$loading_msg,
							$error_msg
		        		);
			$PAGE->requires->js_init_call('M.filter_refshares.init',
											$jsdata,
											false,
											$jsmodule);

			$filter_text = '<div id="rfjs" class="yui3-skin-sam">'.
						'<div id="'.$refshare_div.'"><noscript>'.$javascriptoff_msg.
						'<a href="'.$refshare_rss.'">'.$refshare_rss.'</a></noscript>'.
						'</div></div>';
			return $filter_text;
		}
	}

?>