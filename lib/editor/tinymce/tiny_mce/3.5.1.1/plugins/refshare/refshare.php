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
 * Displays the TinyMCE popup window to insert a Moodle emoticon
 *
 * @package    tinymceplugin
 * @subpackage insertrefshare
 * @copyright  2012 Owen Stephens <owen@ostephens.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* TODO
 * Do check on validity of RefShare URL? On submission of form
 * Allow a lookup of published RefShares on Shared accounts?
 */


define('NO_MOODLE_COOKIES', true); // Session not used here
define('NO_UPGRADE_CHECK', true);  // Ignore upgrade check

require_once(dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))) . '/config.php');

$PAGE->set_context(get_system_context());
$editor = get_texteditor('tinymce');

require_once(dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))) . '/local/references/apibib/apibib_lib.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#refshare_dlg.title}</title>
	<script type="text/javascript" src="../../tiny_mce_popup.js?v={tinymce_version}"></script>
	<script type="text/javascript" src="js/refshare.js?v={tinymce_version}"></script>
</head>
<body>

<form onsubmit="refshareDialog.insert();return false;" action="#">
	<p>Reading List section heading: <input id="f_rlsecheading" name="f_rlsecheading" type="text" class="text" /></p>
	<p>Reading List section notes: <input id="f_rlsecnotes" name="f_rlsecnotes" type="text" class="text" /></p>
	<p>URL of RefWorks RSS Feed: <input id="f_refshare" name="f_refshare" type="text" class="text" /></p>
	<p>Reference Style to be applied: <select id="f_style" name="f_style">
	<?php
	$referencestyles = apibib::get_referencestyles();
	foreach($referencestyles as $style) {
		echo '<option value ="'.$style['string'].'">'.$style['quikbib'].'</option>';
	}
	?>
	</select>
	<div class="mceActionPanel">
		<input type="button" id="insert" name="insert" value="{#insert}" onclick="refshareDialog.insert();" />
		<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
	</div>
</form>

</body>
</html>
