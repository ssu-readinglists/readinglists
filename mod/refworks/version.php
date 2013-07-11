<?php // $Id: version.php,v 1.4 2010/03/18 16:04:49 jp5987 Exp $

/**
 * Code fragment to define the version of refworks
 * This fragment is called by moodle_needs_upgrading() and /admin/index.php
 *
 * @author  Your Name <your@email.address>
 * @version $Id: version.php,v 1.4 2010/03/18 16:04:49 jp5987 Exp $
 * @package mod/refworks
 */

$module->version  = 2013071000;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2010080300; //2.0
$module->cron     = 604800;           // Period for cron to check this module (secs)

?>
