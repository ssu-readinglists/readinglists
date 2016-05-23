<?php // $Id: version.php,v 1.4 2010/03/18 16:04:49 jp5987 Exp $

/**
 * Code fragment to define the version of refworks
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author  Your Name <your@email.address>
 * @version $Id: version.php,v 1.4 2010/03/18 16:04:49 jp5987 Exp $
 * @package mod/refworks
 */

$plugin->version  = 2016052300;  // The current module version (Date: YYYYMMDDXX)
$plugin->requires = 2010080300; //2.0
$plugin->cron     = 604800;           // Period for cron to check this module (secs)
$plugin->component = "mod_refworks";

?>
