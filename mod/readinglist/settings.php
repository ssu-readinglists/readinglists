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
 * Reading list module admin settings and defaults
 *
 * @package    mod
 * @subpackage readinglist
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configmultiselect('readinglist/displayoptions',
        get_string('displayoptions', 'readinglist'), get_string('configdisplayoptions', 'readinglist'),
        $defaultdisplayoptions, $displayoptions));

    //--- modedit defaults -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('readinglistmodeditdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox_with_advanced('readinglist/printheading',
        get_string('printheading', 'readinglist'), get_string('printheadingexplain', 'readinglist'),
        array('value'=>1, 'adv'=>false)));
    $settings->add(new admin_setting_configcheckbox_with_advanced('readinglist/printintro',
        get_string('printintro', 'readinglist'), get_string('printintroexplain', 'readinglist'),
        array('value'=>0, 'adv'=>false)));
    $settings->add(new admin_setting_configselect_with_advanced('readinglist/display',
        get_string('displayselect', 'readinglist'), get_string('displayselectexplain', 'readinglist'),
        array('value'=>RESOURCELIB_DISPLAY_OPEN, 'adv'=>true), $displayoptions));
    $settings->add(new admin_setting_configtext_with_advanced('readinglist/popupwidth',
        get_string('popupwidth', 'readinglist'), get_string('popupwidthexplain', 'readinglist'),
        array('value'=>620, 'adv'=>true), PARAM_INT, 7));
    $settings->add(new admin_setting_configtext_with_advanced('readinglist/popupheight',
        get_string('popupheight', 'readinglist'), get_string('popupheightexplain', 'readinglist'),
        array('value'=>450, 'adv'=>true), PARAM_INT, 7));
}
