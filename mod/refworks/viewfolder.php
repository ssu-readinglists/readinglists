<?php
/**
 * View all references screen
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/refworks_display.php');

refworks_base::init();

$fld = required_param('folder',PARAM_TEXT);
//get if optional sorting param set by user
$sorting = optional_param('sort',7,PARAM_INT);
if ($sorting===7) {
    $fld = refworks_base::return_foldername($fld);
    $sorting = 0;
} else {
    $fld = urldecode($fld);
}

$curbreadcrumb = array(array('name' => get_string('viewfolder', 'refworks', htmlspecialchars(refworks_base::return_foldername($fld,true))), 'type' => 'refworks'));

refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();



refworks_base::write_sidemenu('viewfolder.php?folder='.urlencode($fld));

//Main content goes here

refworks_base::write_heading(get_string('viewfolder', 'refworks',  htmlspecialchars(refworks_base::return_foldername($fld,true))));

refworks_connect::write_login_errors();

?>
<div class="instructions">
<p>
Selecting a folder title allows you to view all references stored within a folder.</p>
<p>
Alongside each reference there are also a number of buttons that allow you to access extra functions for the reference. Access to these extra functions is dependent on the data contained in the reference and the level of available functionality given to you by the system.
</p>
<table width="100%" border="1" summary="List of buttons that give extra functionality for each reference">
  <tr>
    <th width="30" scope="row"><img src="<?php echo refworks_display::get_image_paths()->delfolder;?>" alt="remove from folder icon" width="16" height="16" /></th>
    <td>Remove from folder: Removes the reference from this folder.</td>
  </tr>
  <tr>
    <th scope="row"><img src="<?php echo refworks_display::get_image_paths()->delete; ?>" alt="delete icon" /></th>
    <td>Delete: Permanently deletes the reference.</td>
  </tr>
  <tr>
    <th scope="row"><img src="<?php echo refworks_display::get_image_paths()->edit; ?>" alt="update icon" /></th>
    <td>Update: Allows you to edit the reference data.</td>
  </tr>
  <tr>
    <th scope="row"><img src="<?php echo refworks_display::get_image_paths()->source; ?>" alt="View source icon" width="13" height="16" /></th>
    <td>View resource: Takes you to the resource described by the reference e.g. an article  in an online journal. This option will only appear where a link to the online resource is possible</td>
  </tr>
</table>
</div>
<?php
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}
//check capability
if (!refworks_base::check_capabilities('mod/refworks:folders')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('viewfolder','refworks',$fld)));
    refworks_base::write_footer();
    exit;
}

//before we do anything check that there are references to display
$count = false;
//match $fld with folders from refworks
for ($a=0,$max=count(refworks_folder_api::$folders);$a<$max;$a++) {
    if (refworks_folder_api::$folders[$a]['name']== $fld) {
        $count = (int)refworks_folder_api::$folders[$a]['numrefs'];
    }
}

if ($count==0 || $count===false) {
    if ($count===false) {
        refworks_base::write_error(get_string('folder_notfound','refworks'));
    }
    //no refs so no point in continuing
    refworks_base::write_footer();
    exit;
}


//see what page we are on or if we are all (from optional params)
$showall = optional_param('all',false,PARAM_BOOL);
$curpage = optional_param('page',1,PARAM_INT);

$numperpage = 20;

//call display class to create pagination headings
$curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('viewfolder.php?&amp;sort='.$sorting.'&amp;folder='.urlencode($fld)), $numperpage);

if ($showall) {
    $numperpage = $count;
}
//call refworks retrieve all with page num + amount per page + sorting
$allrefs = refworks_ref_api::get_all_refs($curpage, $numperpage, $sorting, 0, 'htm', $fld);
//send refs returned to display class to create items
refworks_display::write_ref_list($allrefs, refworks_display::folderviewaction);

//if (refworks_base::$isinstance) {
//    add_to_log(refworks_base::$course->id,'refworks','view','viewfolder.php?id='.refworks_base::$cm->id.'&all='.$showall,'View folder',refworks_base::$cm->id);
//}
$curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('viewfolder.php?&amp;sort='.$sorting.'&amp;folder='.urlencode($fld)), $numperpage);
refworks_base::write_footer();
?>