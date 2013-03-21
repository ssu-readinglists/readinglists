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
 * Private readinglist module utility functions
 *
 * @package    mod
 * @subpackage readinglist
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/readinglist/lib.php");


/**
 * File browsing support class
 */
class readinglist_content_file_info extends file_info_stored {
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' and $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

function readinglist_get_editor_options($context) {
    global $CFG;
    return array('subdirs'=>1, 'maxbytes'=>$CFG->maxbytes, 'maxfiles'=>-1, 'changeformat'=>1, 'context'=>$context, 'noclean'=>1, 'trusttext'=>0);
}

function refresh_refshares($text) {
	$search = '/(http:\/\/www\.refworks\.com\/refshare[\/\?][^\s<]*)/';
	if (preg_match_all($search,$text, $matches) > 0) {
		global $CFG;
		require_once($CFG->dirroot.'/filter/refshares/format_refshare.php');
		foreach($matches[0] as $match) {
			$refshare_param = explode('#',$match);
			$refshare_rss = preg_replace('/&amp;/','&',$refshare_param[0]);
			$refshare_style = $refshare_param[1];
			$pattern = '/site=(.*?)\/(.*?)\/(.*?)&(amp;)?rss/';
			preg_match($pattern,$refshare_rss,$matches);
			$refshare_rss = "http://www.refworks.com/refshare?site=".$matches[1]."/".$matches[2]."/".$matches[3]."&rss";
			// This should now match the RSS URL used in javascript filter version
			error_log("Going to expire cache with: ".$refshare_rss."    ".$refshare_style);
			expire_cache($refshare_rss, $refshare_style);
		}
	}
}