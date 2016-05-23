<?php

function local_references_cron() {
	$modconfig = get_config('local_references');
	$timenow = time();
	if (property_exists($modconfig,'lastcroncompleted')) {
		$lastrun = $modconfig->lastcroncompleted;
	} else {
		set_config('lastcroncompleted', 0, 'local_references');
		$lastrun = 0;
	}
	// Check if cron has run in last 23 hours, if so skip it
	if ($timenow < $lastrun + 23*60*60) {
        mtrace("Skipping local_references cron as already run in last 24 hours");
		return;
    } else {
		mtrace("local_references cron not run for at least 24 hours");
	}
	// Check if it's before 1am (most recent midnight + 3600 seconds), if so skip it
    if ($timenow < strtotime('00:00') + 3630) {
        mtrace("Skipping local_references cron as not after 1:30am");
		return;
	} elseif ($timenow > strtotime('00:00') + 3690) {
		mtrace("Skipping local_references cron as after 2:30am");
		return;
	} else {
		mtrace("It is after 1:30am and before 2:30am today so local_references cron can run");
	}
	// Cron hasn't run for at least 23 hours, and it's after 1am, so run cron, unless it's set to never run
	global $CFG;
	require_once(dirname(__FILE__).'/apibib/apibib_lib.php');
	if ($cleartemp = apibib::cleartemp()) {
		mtrace($cleartemp);
	} else {
		mtrace('Unable to run clear temp successfully');
		return;
	}
	// Update the lastcron time
	set_config('lastcroncompleted', $timenow, 'local_references');
	mtrace('Updated time for last successful completion of local references cron');
}