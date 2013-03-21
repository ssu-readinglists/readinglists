<?php

function local_references_cron() {
	global $CFG;
	mtrace("References cron started");
	require_once(dirname(__FILE__).'/apibib/apibib_lib.php');
	$allfolders = apibib::cleartemp();
	mtrace($allfolders);
	mtrace("References cron ran");
}