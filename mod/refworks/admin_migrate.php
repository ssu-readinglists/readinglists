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
 * Hacky admin only page that will let you move collaborative accounts from
 * one moodle installation to another e.g. from a 1.9 install to a 2.0
 * This takes into consideration that user ids might be different...
 * You need to upload a csv file generated using sql:
 * SELECT accs.*, auser.username, users.owner
 * FROM #_refworks_collab_accs accs
 * INNER JOIN #_refworks_collab_users users on accs.id = users.accid
 * INNER JOIN #_user auser on users.userid = auser.id
 * ORDER BY accs.id
 *
 *
 * @package    mod
 * @subpackage refworks
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later (5)
 */

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $OUTPUT, $PAGE, $DB;

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');

admin_externalpage_setup('managemodules');//This is a hack as this page is 'hidden' and not on menu

$context = context_system::instance();
require_capability('mod/refworks:migrate_collab_accs', $context);

$baseurl = '/mod/refworks/admin_migrate.php';

echo $OUTPUT->header();

echo $OUTPUT->box_start('generalbox');

print <<<TEXT
<p>This page can be used to migrate collaborative accounts from another Moodle install.</p>
<p>Upload a CSV file generated from the following SQL: (replace # with table prefix, set delimeter to ;)</p>
<blockquote>
SELECT accs.*, auser.username, users.owner
FROM #_refworks_collab_accs accs
INNER JOIN #_refworks_collab_users users on accs.id = users.accid
INNER JOIN #_user auser on users.userid = auser.id
ORDER BY accs.id
</blockquote>
<p>This process can optionally delete any existing records on the system. Do this at you own risk as the code cannot restore these once deleted!</p>
TEXT;

echo $OUTPUT->box_end();

//Upload csv form...
class refworks_adminmigrate_form extends moodleform {
    function definition() {

        $mform    =& $this->_form;
        $mform->addElement('filepicker', 'csv', 'CSV file with collab accounts', null, array('maxbytes' => 512000, 'accepted_types' => '*.csv'));
        $mform->addElement('checkbox', 'deleterecs', 'Delete existing records.');
        $this->add_action_buttons(false, 'Start migration');
    }
}

$myform = new refworks_adminmigrate_form();

if ($fromform = $myform->get_data()) {
    //submitted

    //Process will:
    //Get CSV file, import and check validity
    //Delete existing records - eek!
    //Run through CSV row:
    //1. check if old account id has already been added - if not add account details
    //2. Get user details based on username - skip if no user
    //3. Create user acc record based on new acc id and new user id
    $csvcontents = $myform->get_file_content('csv');

    $iid = csv_import_reader::get_new_iid('refworksmigrate');
    $cir = new csv_import_reader($iid, 'refworksmigrate');

    $contents = utf8_encode($csvcontents);

    $readcount = $cir->load_csv_content($contents, 'UTF-8', 'semicolon');
    if (empty($readcount)) {
        print_error('csvfailed', 'data');
    }
    if (!$fieldnames = $cir->get_columns()) {
        print_error('cannotreadtmpfile', 'error');
    }
    if (count($fieldnames) != 9) {
        print_error('cannotreadtmpfile', 'error');
    }

    //Use transaction so if any problem we rollback transfer
    $transaction = $DB->start_delegated_transaction();

    //Delete existing rows here
    if (isset($fromform->deleterecs)) {
        $DB->delete_records('refworks_collab_accs');
        $DB->delete_records('refworks_collab_users');
    }

    $addedalready = array();
    $newid = array();

    $cir->init();
    while ($record = $cir->next()) {
        //first see if we need to add the refworks accound (based on row id)
        if (!in_array($record[0], $addedalready)) {
            //check if account already exists?
            if ($accexists = $DB->get_field('refworks_collab_accs', 'id', array('name' => trim($record[1], "'"), 'login' => trim($record[2], "'")))) {
                $newidnum = $accexists;
            } else {
                $newacc = new stdClass();
                $newacc->name = trim($record[1], "'");
                $newacc->login = trim($record[2], "'");
                $newacc->password = trim($record[3], "'");
                $newacc->created = $record[4];
                if ($record[5] != '') {
                    $newacc->lastlogin = $record[5];
                }
                if ($record[6] != '') {
                    $newacc->datedeleted = $record[6];
                }
                if ($newidnum = $DB->insert_record('refworks_collab_accs', $newacc, true)) {
                    $addedalready[] = $record[0];
                    $newid[$record[0]] = $newidnum;
                }
            }
        } else {
            //get the new id num
            $newidnum = $newid[$record[0]];
        }

        //add in user record (check username in this system first)
        if ($userrec = $DB->get_field('user', 'id', array('username' => trim($record[7], "'")))) {
            $newuser = new stdClass();
            $newuser->userid = $userrec;
            $newuser->accid = $newidnum;
            $newuser->owner = $record[8];
            $DB->insert_record('refworks_collab_users', $newuser);
        } else {
            echo '<p>Could not find user: <em>'.trim($record[7], "'").'</em> on this system.</p>';
        }
    }

    $transaction->allow_commit();

    echo $OUTPUT->notification('Upload completed.');
} else {
    $myform->display();
}

echo $OUTPUT->footer();