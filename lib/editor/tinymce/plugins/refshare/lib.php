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

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin for Moodle emoticons.
 *
 * @package   tinymce_refshare
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tinymce_refshare extends editor_tinymce_plugin {
    /** @var array list of buttons defined by this plugin */
    protected $buttons = array('refshare');

    protected function update_init_params(array &$params, context $context,
            array $options = null) {
        global $OUTPUT;
        // Only show button if filter is active
        if ($this->get_config('requirerefshares', 1)) {
            // If RefShares filter is disabled, do not add button.
            $filters = filter_get_active_in_context($context);
            if (!array_key_exists('refshares', $filters)) {
                return;
            }
        }
        // Only show button if users have permssion to edit reading lists
        if (!has_capability('mod/readinglist:edit',context_system::instance())){
            return;
        }
        // Add button at the end of the first row
        $this->add_button_after($params, 1, 'refshare');
        // Add JS file, which uses default name.
        $this->add_js_plugin($params);
    }
}
