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
 * AJAX script to respond to RefShare formatting requests
 *
 * Returns a JSON object containing formatted RefShare results
 *
 * @package    filter_refshares
 * @copyright  Owen Stephens, ostephens.com
 * @author     Owen Stephens <owen@ostephens.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once('format_refshare.php');

$refshare = required_param('refshare', PARAM_TEXT);
$style = required_param('style', PARAM_TEXT);

$output = new stdClass;

// RefShare URL passed to this script will be url-encoded. Should decode before passing on if want to use existing cached version
$output = cached_refshare($refshare, $style);
if (strlen($output) > 0) {
    echo json_encode(utf8_encode($output));
} else {
	echo json_encode(get_string('usererrormsg','filter_refshares').'<a href="'.$refshare.'">'.
		$refshare.'</a>.');
}
