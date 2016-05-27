<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task for refshares cron.
 *
 * @author owen@ostephens.com
 * @package    filter_refshares
 * @copyright  2016 Owen Stephens
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_refshares\task;

class cron_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('crontask', 'filter_refshares');
    }

    public function execute() {
        global $CFG, $DB;
        $start_time = microtime(true);
        $table = 'cache_filters';
        $refshare_records = $DB->get_records($table, array('filter' => 'refshares'));
        $refshares_updated = 0;
        if(!$timeout = $CFG->filter_refshares_timeout) {
            $timeout = 1800;
        }
        mtrace("TIMEOUT: ".$timeout);
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
            if ($timer > $timeout) {
                mtrace("RefShares filter cron has been executing for more than ".$timeout." seconds. Exiting to avoid taking too much time. Any expired caches that have not been refreshed will be picked up in the next run");
                break;
            }
        }
        if ($refshares_updated > 0) {
            mtrace("Found ".$refshares_updated." RefShare caches that needed updating, any failures will have been written to error_log");
        } else {
            mtrace("No cached RefShares needed updating");
        }
    // Update the lastcroncompleted time representing last successful completion of cron job
    set_config('lastcroncompleted', time(), 'filter_refshares');  
    }
}
