<?php
$settings->add(new admin_setting_configtext('mod_refworks/refworks_name', get_string('refworksname', 'refworks'),
                   get_string('refworks_name_desc', 'refworks'), get_string('modulename', 'refworks')));


$settings->add(new admin_setting_configtext('mod_refworks/refworks_link', get_string('refworks_link', 'refworks'),
                   get_string('refworks_link_desc', 'refworks'), 'http://www.refworks.com/refworks/'));

$settings->add(new admin_setting_configtext('mod_refworks/refworks_styles', get_string('refworks_styles', 'refworks'),
                   get_string('refworks_styles_desc', 'refworks'), ''));

$settings->add(new admin_setting_configcheckbox('mod_refworks/refworks_shownewlogin', get_string('refworks_shownewlogin', 'refworks'),
                    get_string('refworks_shownewlogin_desc', 'refworks'), 0));