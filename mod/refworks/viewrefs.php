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

$curbreadcrumb = array(array('name' => get_string('view_all_refs', 'refworks'), 'type' => 'refworks'));

refworks_base::write_header($curbreadcrumb);

//init rwapi
refworks_connect::require_login();

refworks_base::write_sidemenu();

//Main content goes here

refworks_base::write_heading(get_string('view_all_refs', 'refworks'));

refworks_connect::write_login_errors();

?>
<div class="instructions">
<p>
On this screen you can view a list of all of the references that are stored in your personal library.
</p>
<p>
Alongside each reference there are also a number of buttons that allow you to access extra functions for the reference. Access to these extra functions is dependent on the data contained in the reference and the level of available functionality given to you by the system.
</p>
<table width="100%" border="1" summary="List of buttons that give extra functionality for each reference">
  <tr>
    <th width="30" scope="row"><img src="<?php echo refworks_display::get_image_paths()->folder; ?>" alt="add to folder icon" width="16" height="16" /></th>
    <td>Add to folder: Adds  the reference to a folder in your library.</td>
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
    <td>View resource: Takes you to the resource described by the reference e.g. an article  in an online journal. This option will only appear where a link to the online resource is possible.</td>
  </tr>
</table>
</div>
<?php
if (!refworks_connect::$connectok) {
    //No RefWorks session? Do nothing instead.
    refworks_base::write_footer();
    exit;
}
//before we do anything check that there are references to display
$count = refworks_ref_api::count_all_refs();
if ($count==0 || $count===false) {
    if ($count===false) {
        refworks_base::write_error(get_string('general_error','refworks'));
    }
    //no refs so no point in continuing
    refworks_base::write_footer();
    exit;
}

//get if optional sorting param set by user
$sorting = optional_param('sort',9,PARAM_INT);
//see what page we are on or if we are all (from optional params)
$showall = optional_param('all',false,PARAM_BOOL);
$curpage = optional_param('page',1,PARAM_INT);

$numperpage = 20;

//call display class to create pagination headings
$curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('viewrefs.php?&amp;sort='.$sorting), $numperpage);


if ($showall) {
    $numperpage = $count;
}
//call refworks retrieve all with page num + amount per page + sorting
$allrefs = refworks_ref_api::get_all_refs($curpage, $numperpage, $sorting, 0);
//send refs returned to display class to create items
refworks_display::write_ref_list($allrefs, refworks_display::viewaction);

if (refworks_base::$isinstance) {
    add_to_log(refworks_base::$course->id,'refworks','view','viewrefs.php?id='.refworks_base::$cm->id.'&all='.$showall,'View all references',refworks_base::$cm->id);
}
refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('viewrefs.php?&amp;sort='.$sorting), $numperpage);
refworks_base::write_footer();

?>