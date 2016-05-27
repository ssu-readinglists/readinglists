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

}