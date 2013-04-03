<?php

function local_references_cron() {
	global $CFG;
	require_once(dirname(__FILE__).'/apibib/apibib_lib.php');
	if ($cleartemp = apibib::cleartemp()) {
		mtrace($cleartemp);
	} else {
		mtrace('Unable to run clear temp successfully');
	}
}