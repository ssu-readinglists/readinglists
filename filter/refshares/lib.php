<?php

function filter_refshares_cron() {
	$modconfig = get_config('filter_refshares');
	$timenow = time();
	if (property_exists($modconfig,'lastcroncompleted')) {
		$lastrun = $modconfig->lastcroncompleted;
	} else {
		set_config('lastcroncompleted', 0, 'local_references');
		$lastrun = 0;
	}
	// Check if cron has run in last 23 hours, if so skip it
	if ($timenow < $lastrun + 23*60*60) {
        mtrace("Skipping filter_reshares cron as already run in last 24 hours");
		return;
    } else {
		mtrace("filter_refshares cron not run for at least 24 hours");
	}
	// Check if it's before 1am (most recent midnight + 3600 seconds), if so skip it
    if ($timenow < strtotime('00:00') + 3600) {
        mtrace("Skipping filter_reshares cron as not after 1am");
		return;
	} elseif ($timenow > strtotime('00:00') + 3660) {
		mtrace("Skipping filter_reshares cron as it is after 2am");
		return;
	} else {
		mtrace("It is after 1am and before 2am today so cron can run");
	}
	// Cron hasn't run for at least 23 hours, and it's after 1am, so run cron, unless it's set to never run
	global $CFG, $DB;
	if ($CFG->filter_refshares_cronwindow == 0) {
		mtrace("Refshares filter cronjob set to never run");
	} else {
		$start_time = microtime(true);
		$table = 'cache_filters';
		$refshare_records = $DB->get_records($table, array('filter' => 'refshares'));
	    $refshares_updated = 0;
		foreach ($refshare_records as $rec) {
			if (time()-$rec->timemodified > $CFG->filter_refshares_cacheexpires) {
				mtrace("Cache expired, needs refreshing. ID in cache_filters table = ".$rec->id);
				//Search for Refshare RSS and style in raw text
				$search = '/title="(http:\/\/www\.refworks\.com\/refshare[\/\?][^"]*)/is';
				$formatted_refs = stripslashes(htmlspecialchars_decode($rec->rawtext));
		    	if (preg_match($search, $formatted_refs, $matches) == 1) {
					$refshare_param = explode('#',$matches[1]);
					$refshare_rss = preg_replace('/&amp;/','&',$refshare_param[0]);
					$refshare_style = $refshare_param[1];
					$pattern = '/site=(.*?)\/(.*?)\/(.*?)&(amp;)?rss/';
					preg_match($pattern,$refshare_rss,$matches);

					$refshare_url_encoded = urlencode("http://www.refworks.com/refshare?site=").
											$matches[1]."%2F".$matches[2]."%2F".urlencode($matches[3]).
											"%26rss";
					$refshare_url = urldecode($refshare_url_encoded);
					require_once($CFG->dirroot.'/filter/refshares/format_refshare.php');
					if(update_cached_refshare($refshare_url, $refshare_style)) {
						$refshares_updated += 1;
					} else {
						mtrace("Failed to update cache for: ".$refshare_param[0]);
					}
				} else {
					mtrace("RefShare details not found in record. ID in cache_filters table is ".$rec->id);
				}
			}
			$timer = microtime(true) - $start_time;
			if ($timer > 300) {
				mtrace("RefShares filter cron has been executing for more than 5 minutes. Exiting to avoid taking too much time. Any expired caches that have not been refreshed will be picked up in the next run");
				break;
			}
		}
		if ($refshares_updated > 0) {
			mtrace("Cron job found ".$refshares_updated." RefShare caches that needed updating, any failures will have been written to error_log");
		} else {
			mtrace("No cached RefShares needed updating");
		}
	// Update the lastcroncompleted time representing last successful completion of cron job
	set_config('lastcroncompleted', $timenow, 'filter_refshares');
	}
}
