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
 * @package    local_references
 * @copyright  2016 Owen Stephens
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_references\task;

class cron_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('crontask', 'local_references');
    }

    public function execute() {
    global $CFG;
    require_once(dirname(__FILE__).'/apibib/apibib_lib.php');
    if ($cleartemp = apibib::cleartemp()) {
        mtrace($cleartemp);
    } else {
        mtrace('Unable to run clear temp successfully');
        return;
    }
    // Update the lastcron time
    set_config('lastcroncompleted', time(), 'local_references');
    }
        
}
