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
 * @package    mod
 * @subpackage readinglist
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in Reading list module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function readinglist_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function readinglist_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function readinglist_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function readinglist_get_view_actions() {
    return array('view','view all');
}

/**
 * List of update style log actions
 * @return array
 */
function readinglist_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add readinglist instance.
 * @param object $data
 * @param object $mform
 * @return int new readinglist instance id
 */
function readinglist_add_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;
    $draftitemid = $data->readinglist['itemid'];

    $data->timemodified = time();
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = $data->printheading;
    $displayoptions['printintro']   = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

    $data->content       = $data->readinglist['text'];
    $data->contentformat = $data->readinglist['format'];

    $data->id = $DB->insert_record('readinglist', $data);

    // we need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    $context = context_module::instance($cmid);

    if ($draftitemid) {
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_readinglist', 'content', 0, readinglist_get_editor_options($context), $data->content);
        $DB->update_record('readinglist', $data);
    }

    return $data->id;
}

/**
 * Update readinglist instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function readinglist_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;
    $draftitemid = $data->readinglist['itemid'];

    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision++;

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = $data->printheading;
    $displayoptions['printintro']   = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

    $data->content       = $data->readinglist['text'];
    $data->contentformat = $data->readinglist['format'];

    $DB->update_record('readinglist', $data);

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_readinglist', 'content', 0, readinglist_get_editor_options($context), $data->content);
        $DB->update_record('readinglist', $data);
    }

    return true;
}

/**
 * Delete readinglist instance.
 * @param int $id
 * @return bool true
 */
function readinglist_delete_instance($id) {
    global $DB;

    if (!$readinglist = $DB->get_record('readinglist', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('readinglist', array('id'=>$readinglist->id));

    return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $readinglist
 * @return object|null
 */
function readinglist_user_outline($course, $user, $mod, $readinglist) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'readinglist',
                                              'action'=>'view', 'info'=>$readinglist->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new stdClass();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return NULL;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $readinglist
 */
function readinglist_user_complete($course, $user, $mod, $readinglist) {
    global $CFG, $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'readinglist',
                                              'action'=>'view', 'info'=>$readinglist->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string('neverseen', 'readinglist');
    }
}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param cm_info $coursemodule
 * @return cached_cm_info Info to customise main page display
 */
function readinglist_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$readinglist = $DB->get_record('readinglist', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $readinglist->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('readinglist', $readinglist, $coursemodule->id, false);
    }

    if ($readinglist->display != RESOURCELIB_DISPLAY_POPUP) {
        return $info;
    }

    $fullurl = "$CFG->wwwroot/mod/readinglist/view.php?id=$coursemodule->id&amp;inpopup=1";
    $options = empty($readinglist->displayoptions) ? array() : unserialize($readinglist->displayoptions);
    $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
    $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
    $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
    $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    return $info;
}


/**
 * Lists all browsable file areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function readinglist_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('content', 'readinglist');
    return $areas;
}

/**
 * File browsing support for readinglist module content area.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function readinglist_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_readinglist', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_readinglist', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/readinglist/locallib.php");
        return new readinglist_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: readinglist_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the readinglist files.
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function readinglist_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/readinglist:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    // $arg could be revision number or index.html
    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // serve readinglist content
        $filename = $arg;

        if (!$readinglist = $DB->get_record('readinglist', array('id'=>$cm->instance), '*', MUST_EXIST)) {
            return false;
        }

        // remove @@PLUGINFILE@@/
        $content = str_replace('@@PLUGINFILE@@/', '', $readinglist->content);

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $content = format_text($content, $readinglist->contentformat, $formatoptions);

        send_file($content, $filename, 0, 0, true, true);
    } else {
		return false;
	}
}


/**
 * This function extends the global navigation for the site.
 * It is important to note that you should not rely on PAGE objects within this
 * body of code as there is no guarantee that during an AJAX request they are
 * available
 *
 * @param navigation_node $navigation The readinglist node within the global navigation
 * @param stdClass $course The course object returned from the DB
 * @param stdClass $module The module object returned from the DB
 * @param stdClass $cm The course module instance returned from the DB
 */
function readinglist_extend_navigation($navigation, $course, $module, $cm) {
    /**
     * This is currently just a stub so that it can be easily expanded upon.
     * When expanding just remove this comment and the line below and then add
     * you content.
     */
    $navigation->nodetype = navigation_node::NODETYPE_LEAF;
}

/**
 * Return a list of readinglist types
 * @param string $readinglisttype current readinglist type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function readinglist_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-readinglist-*'=>get_string('page-mod-readinglist-x', 'readinglist'));
    return $module_pagetype;
}

/**
 * Export readinglist resource contents
 *
 * @return array of file content
 */
function readinglist_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = array();
    $context = context_module::instance($cm->id);

    $readinglist = $DB->get_record('readinglist', array('id'=>$cm->instance), '*', MUST_EXIST);

    // readinglist contents
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_readinglist', 'content', 0, 'sortorder DESC, id ASC', false);
    foreach ($files as $fileinfo) {
        $file = array();
        $file['type']         = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_readinglist/content/'.$readinglist->revision.$fileinfo->get_filepath().$fileinfo->get_filename(), true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $contents[] = $file;
    }

    // readinglist html conent
    $filename = 'index.html';
    $readinglistfile = array();
    $readinglistfile['type']         = 'file';
    $readinglistfile['filename']     = $filename;
    $readinglistfile['filepath']     = '/';
    $readinglistfile['filesize']     = 0;
    $readinglistfile['fileurl']      = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_readinglist/content/' . $filename, true);
    $readinglistfile['timecreated']  = null;
    $readinglistfile['timemodified'] = $readinglist->timemodified;
    // make this file as main file
    $readinglistfile['sortorder']    = 1;
    $readinglistfile['userid']       = null;
    $readinglistfile['author']       = null;
    $readinglistfile['license']      = null;
    $contents[] = $readinglistfile;

    return $contents;
}
