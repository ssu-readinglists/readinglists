<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
			
$formats = array('0' => 'Do not cache', '60' => '1 minute', '900' => '15 minutes', '3600' => '1 hour', '86400' => '1 day', '604800' => '1 week', '31449600' => '52 weeks');
$settings->add(new admin_setting_configselect('filter_refshares_cacheexpires', get_string('cacheexpires', 'filter_refshares'), 
													get_string('cacheexpiresconfig', 'filter_refshares'), '3600', $formats));

$link_behaviours = array('newwin' => 'Open in new tab/window', 'samewin' => 'Open in same tab/window');
$settings->add(new admin_setting_configselect('filter_refshares_linkbehaviour', get_string('linkbehaviour', 'filter_refshares'), 
											get_string('linkbehaviourconfig', 'filter_refshares'), 'newwin', $link_behaviours));

$usejs = array('no' => 'Use original filter, no javascript', 'yes' => 'Use javascript powered filter');
$settings->add(new admin_setting_configselect('filter_refshares_usejs', get_string('usejs', 'filter_refshares'), 
											get_string('usejsconfig', 'filter_refshares'), 'no', $usejs));

/* Not yet implemented 
$earliestcron = array('00:00' => 'Midnight', '01:00' => '01:00', '02:00' => '02:00', '03:00' => '03:00', '04:00' => '04:00',
					  '05:00' => '05:00', '06:00' => '06:00', '07:00' => '07:00', '08:00' => '08:00', '09:00' => '09:00',
					  '10:00' => '10:00', '11:00' => '11:00', '12:00' => 'Midday', '13:00' => '13:00', '14:00' => '14:00',
					  '15:00' => '15:00', '16:00' => '16:00', '17:00' => '17:00', '18:00' => '18:00', '19:00' => '19:00',
					  '20:00' => '20:00', '21:00' => '21:00', '22:00' => '22:00', '23:00' => '23:00', 'none' => 'No restriction');
$settings->add(new admin_setting_configselect('filter_refshares_earliestcron', get_string('earliestcron', 'filter_refshares'), 
											get_string('earliestcronconfig', 'filter_refshares'), 'none', $earliestcron));
*/
// Support only binary option at the moment
$cronwindow = array('1' => 'Always run','0' => 'Never run');
//$cronwindow = array('0' => 'Never run', '900' => '15 minutes', '1800' => '30 minutes', '3600' => '1 hour', '7200' => '2 hours','10800' => '3 hours','14400' => '4 hours', '18000' => '5 hours', '21600' => '6 hours', '43200' => '12 hours');
$settings->add(new admin_setting_configselect('filter_refshares_cronwindow', get_string('cronwindow', 'filter_refshares'), 
											get_string('cronwindowconfig', 'filter_refshares'), 'none', $cronwindow));


}