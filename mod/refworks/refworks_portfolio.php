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
 * Portfolio connector
 *
 * @package    mod
 * @subpackage refworks
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/portfolio/caller.php');


class refworks_portfolio_caller extends portfolio_module_caller_base {

    //SPECIAL OVERRIDES AS WE MAY NOT HAVE MODULE INSTANCE

    public function get_navigation() {
        if ($this->cm) {
            $extranav = array('name' => $this->cm->name, 'link' => $this->get_return_url());
            return array($extranav, $this->cm);
        } else {
            $extranav = array('name' => $this->display_name(), 'link' => $this->get_return_url());
            return array($extranav, null);
        }
    }


    public function get_return_url() {
        global $CFG;
        $url = $CFG->wwwroot . '/mod/refworks/createbib.php';
        if ($this->cm) {
            $url .= '?id=' . $this->cm->id;
        }
        return $url;
    }

    public function heading_summary() {
        global $CFG;
        $string = $CFG->wwwroot . '/mod/refworks/createbib.php';
        if ($this->cm) {
            $string .= ': ' . $this->cm->name;
        }
        return get_string('exportingcontentfrom', 'portfolio', $string);
    }

    public function set_context($PAGE) {
        if ($this->cm) {
            $PAGE->set_cm($this->cm);
        }
    }

    //"STANDARD" Method overrides

    protected $result;

    public static function base_supported_formats() {
        return array(PORTFOLIO_FORMAT_PLAINHTML);
    }

    public static function expected_callbackargs() {
        return array(
            'result' => true,
            'id' => true,
        );
    }

    public function load_data() {

        $this->result = html_entity_decode($_POST['ca_result'], ENT_QUOTES, 'utf-8');

        if (!$this->cm = get_coursemodule_from_instance('refworks', $this->id)) {
            //throw new portfolio_caller_exception('invalidcoursemodule');
            $this->cm = null;
        }

    }

    public function prepare_package() {
        $this->get('exporter')->write_new_file($this->result, clean_filename(get_string('modulename', 'refworks') . '-bib.htm'), false);
    }

    public function expected_time() {
        // a file based export
        return $this->expected_time_file();
    }

    public function get_sha1() {
        return sha1($this->result);
    }

    public function check_permissions() {
        return true;
    }

    public static function display_name() {
        return get_string('modulename', 'refworks');
    }

}