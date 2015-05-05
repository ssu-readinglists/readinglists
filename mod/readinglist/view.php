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
 * Reading list module version information
 *
 * @package    mod
 * @subpackage readinglist
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/readinglist/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // Reading list instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
$refresh = optional_param('refresh', 0, PARAM_BOOL);

if ($p) {
    if (!$readinglist = $DB->get_record('readinglist', array('id'=>$p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('readinglist', $readinglist->id, $readinglist->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('readinglist', $id)) {
        print_error('invalidcoursemodule');
    }
    $readinglist = $DB->get_record('readinglist', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/readinglist:view', $context);

//add_to_log($course->id, 'readinglist', 'view', 'view.php?id='.$cm->id, $readinglist->id, $cm->id);
$eventdata = array();
$eventdata['objectid'] = $cm->id;
$eventdata['context'] = $context;
$event = \mod_readinglist\event\course_module_viewed::create($eventdata);
$event->add_record_snapshot('course', $PAGE->course);
$event->trigger();

// Update 'viewed' state if required by completion system
require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/readinglist/view.php', array('id' => $cm->id));

$options = empty($readinglist->displayoptions) ? array() : unserialize($readinglist->displayoptions);

if ($inpopup and $readinglist->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_pagelayout('popup');
    $PAGE->set_title($course->shortname.': '.$readinglist->name);
    if (!empty($options['printheading'])) {
        $PAGE->set_heading($readinglist->name);
    } else {
        $PAGE->set_heading('');
    }
    echo $OUTPUT->header();

} else {
    $PAGE->set_title($course->shortname.': '.$readinglist->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($readinglist);
    echo $OUTPUT->header();

    if (!empty($options['printheading'])) {
        echo $OUTPUT->heading(format_string($readinglist->name), 2, 'main', 'readinglistheading');
    }
}

// Before display, check if refresh has been requested...
if($refresh) {
	// If so, force refresh of all RefShares
	refresh_refshares($readinglist->content);
    // Treat 'refresh' as time list last updated
    $readinglist->timemodified = time();
}

if (!empty($options['printintro'])) {
    if (trim(strip_tags($readinglist->intro))) {
        echo $OUTPUT->box_start('mod_introbox', 'readinglistintro');
        echo format_module_intro('readinglist', $readinglist, $cm->id);
        echo $OUTPUT->box_end();
    }
}

$content = file_rewrite_pluginfile_urls($readinglist->content, 'pluginfile.php', $context->id, 'mod_readinglist', 'content', $readinglist->revision);
if (has_capability('mod/readinglist:manage', $context)) {
	// Add in link back to this page with 'refresh'
	// Check correct syntax
	$content = '<div style="font-size: small;">[<a href="view.php?id='.$cm->id.
					'&refresh=1">'.get_string('refreshrefshares', 'readinglist').'</a>]</div>'.$content;
}

$content .= '<div id="readinglistfooter">'.$readinglist->footer.'</div>';
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $readinglist->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

$strlastmodified = get_string("lastmodified");
echo "<div class=\"modified\">$strlastmodified: ".userdate($readinglist->timemodified)."</div>";

echo $OUTPUT->footer();
