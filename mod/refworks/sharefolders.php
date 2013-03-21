<?php
/**
 * Front screen of module (duplicate of index.php)
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author j.ackland-snow@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */
require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/refworks_display.php');
require_once($CFG->libdir.'/formslib.php');
refworks_base::init();
//$fld = 'unsubmitted';
//$fld = optional_param('foldername', 'unsubmitted', PARAM_TEXT);
//$fld = refworks_base::return_foldername($fld);
//$namey = optional_param('namey', 'unsubmitted', PARAM_TEXT);
//$oldname = optional_param('fldold', 'unsubmitted', PARAM_TEXT);
//write header
$curbreadcrumb = array(array('name' => get_string('share_folder', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);

function common_prelimininaries() {
    //check session key if form actioned
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }
    if (!refworks_connect::$connectok) {
        print_error('connection_error','refworks',refworks_base::return_link('sharefolders.php'));
    }

}
function common_postlimininaries() {
    refworks_base::write_sidemenu();
    refworks_base::write_heading(get_string('share_folder', 'refworks'));
    if (!refworks_base::check_capabilities('mod/refworks:folders')) {
        refworks_base::write_error(get_string('nopermissions','error',get_string('share_folder','refworks')));
        refworks_base::write_footer();
        exit;
    }
    refworks_connect::write_login_errors();
    if (!refworks_connect::$connectok) {
        //No RefWorks session? Do nothing instead.
        refworks_base::write_footer();
        exit;
    }
    global $SESSION;
    if (!isset($SESSION->rwteam)) {
         refworks_base::write_error(get_string('team_noaccess','refworks',get_string('share_folder','refworks')));
         refworks_base::write_footer();
         exit;
    }
}

refworks_connect::require_login();


$refer = refworks_base::return_link('sharefolders.php');

// if form action
$actiontype = '';
$actiontype = optional_param('actiontype','',PARAM_TEXT);
$foldername = refworks_base::return_foldername(optional_param('foldername','',PARAM_RAW));
//check for form submission
if ($actiontype=='remove') {
    //remove sharing action
    common_prelimininaries();
    refworks_folder_api::remove_refshares($foldername);
}else if ($actiontype=='share') {
    //share folder action
    common_prelimininaries();
    refworks_folder_api::add_refshares($foldername);
}

common_postlimininaries();
?>
<div class="instructions"><p>
On this screen you can 'share' any folders in your library. Whilst a folder is 'shared' it can be accessed (read-only) by other RefWorks users via the url in the hyperlink shown below. An RSS feed of the folder is also available whilst it is 'shared'.
</p>
</div>
<?php
$refshares = refworks_folder_api::get_refshares();
refworks_display::write_folder_list(refworks_folder_api::$folders, refworks_display::sharefoldersaction, refworks_folder_api::$sharedfolders);
refworks_base::write_footer();
?>