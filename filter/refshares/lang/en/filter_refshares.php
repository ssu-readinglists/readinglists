<?php

$string['filtername'] = 'RefShares Filter';
$string['settingsheading'] = 'RefShares Filter Settings';
$string['settingsinfo'] = 'Filters for a RefShare URL + reference style using pattern [refshare url]#[style] e.g. http://www.refworks.com/refshare/?site=015791142406000000/RWWS3A1351696/Telstar&amp;rss#harvard. Replaces with formatted list of references using specified style';
$string['usererrormsg'] = 'We are experiencing problems with RefWorks, and it is not currently possible to display this reading list. You can try viewing the list by clicking the link below. Alternatively please try again later or report to <a href="mailto:[local data - email address]">[local data - email address]</a><br />Apologies for any inconvenience caused.<br />';
$string['cacheexpires'] = 'Expire cache after...';
$string['cacheexpiresconfig'] = 'Time after which a cached styled RefShare should be regarded as due to be refreshed';
$string['linkbehaviour'] = 'Reference links ...';
$string['linkbehaviourconfig'] = 'Control whether links from references open in the current or a new window/tab in the browser';
$string['usejs'] = 'Use javascript powered filter?';
$string['usejsconfig'] = 'Control whether the filter is javascript based, or relies on processing filter entirely in php';
$string['earliestcron'] = 'Earliest cron. The start time each day when the cron job to refresh all cached filters can run';
$string['earliestcronconfig'] = 'This setting works together with the "Cron Window" configuration setting. The cron job to refresh cached filters will only run if it is after this time, and the length of time specified in the Window has not yet passed. For example if the settings are "01:00" and "1 hour", the cron job will only run if it is between 1am and 2am. If set to "none" there is no restriction.';
$string['cronwindow'] = 'Run cron?'; 
$string['cronwindowconfig'] = 'Set if the cron job for this filter runs or not';
$string['crontask'] = 'Refshare Filter maintenance jobs';
$string['userloadingmsg'] = 'The references are being fetched from Refworks, please be patient<br />';
$string['javascriptoff'] = 'Your browser does not support javascript, or support for javascript has been switched off. You can try viewing the list by clicking the link below. Contact [local data - email address] for more information.<br />';

?>