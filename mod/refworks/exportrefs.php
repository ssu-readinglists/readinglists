<?php
/**
 * Front screen of module (duplicate of index.php)
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
require_once(dirname(__FILE__).'/../../local/references/export.php');

refworks_base::init();

//This page has two display states:
// 1. Standard view where user selects option
// 2. Export menu display, which has 3 states
//     1. Initial display
//     2. Submission of export
//    3. submission of download (no headers etc)

//Logic to determine display state:
//IF idlist - export2/3 - call export class and check for submittedtype and don't display headers
//else display headers as normal
//IF export is set - export1 - call export class
// else view 1

//get if optional sorting param set by user
$sorting = optional_param('sort',0,PARAM_INT);
//see what page we are on or if we are all (from optional params)
$showall = optional_param('all',false,PARAM_BOOL);
$curpage = optional_param('page',1,PARAM_INT);
//checkbox ids user selected
$selrefs = optional_param('selected','',PARAM_SEQUENCE);
$selfolders = refworks_base::return_foldername(optional_param('selectedfl','',PARAM_RAW));
$selallrefs = optional_param('allrefs','',PARAM_TEXT);
//If submitted form via export button (state 2.1)
$selexport = optional_param('export','no',PARAM_TEXT);
//idlist of selected sent to export functions (i.e. in state 2.2 or 2.3)
$idlist = optional_param('idlist','',PARAM_SEQUENCE);
$folderlist = refworks_base::return_foldername(optional_param('folderlist','',PARAM_RAW));

//Nasty class used to access reference_export as has protected functions
class refworks_exportrefs extends references_export{
    public function return_export_menu(&$options) {
        return parent::return_export_menu($options);
    }
    public function return_submitted() {
        return parent::return_submitted();
    }
    public function return_format_export_type($name) {
        return parent::return_format_export_type($name);
    }
    public function start_export_action($name,$data,$options=NULL) {
        return parent::start_export_action($name,$data,$options);
    }
}

if ($selexport!='no') {
    //State 2.1
    refworks_exportrefs_writeheader();

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }

    //init rwapi
    refworks_connect::require_login();
    refworks_base::write_sidemenu();

    //Main content goes here

    refworks_base::write_heading(get_string('export_ref', 'refworks'));
    refworks_connect::write_login_errors();
    if (!refworks_connect::$connectok) {
        //No RefWorks session? Do nothing instead.
        refworks_base::write_footer();
        exit;
    }

    $selectedrefs = '';
    //Check if the select all refs option is checked
    if ($selallrefs!='') {
        $selrefs = refworks_ref_api::get_all_refs(1,9999);
    }
    //Transfer selected form val into idlist and folderlist fields
    if ($selrefs != '') {
        $selectedrefs = $selrefs;
        if (strpos($selrefs,'&id=')!==false) {
            //selrefs is returned as &id=... rest of code works on comma seperated, convert
            $selrefs=str_replace('&id=',',',$selrefs);
            $selectedrefs = substr($selrefs,1);
        }
    } else {
        //If selected is empty - user may not have js so check POST vars for any checkbox elements (ref_ or fol_)
        foreach ($_POST as $param => $val) {
            if (strpos($param,'r_')===0 && $val==1) {
                if ($selectedrefs!='') {
                    $selectedrefs .= ','.$param;
                } else {
                    $selectedrefs = $param;
                }
            }
        }
    }

    //Transfer folder list into folderlist param
    if ($selfolders == '') {
        foreach ($_POST as $param => $val) {
            if (strpos($param,'fl_')===0) {
                if ($folderlist!='') {
                    $folderlist .= '@@'.$param;
                } else {
                    $folderlist = $param;
                }
            }
        }
    } else {
        $folderlist = $selfolders;
    }

    if ($selectedrefs=='' && $folderlist=='') {
        //NOTHING TO EXPORT!
        refworks_base::write_error(get_string('refexport_error_noneselected','local_references'));
        refworks_base::write_footer();
        exit;
    }

    $exportmen = new refworks_exportrefs();
    $exportmen->initexport();

    $menopts = new stdClass();
    $menopts->hidden = array('idlist','folderlist');
    $menopts->hiddenvals=array($selectedrefs,refworks_base::return_foldername($folderlist, true));
    $menopts->ignore = array('RefWorksDE');
    echo $exportmen->return_export_menu($menopts);

    refworks_base::write_footer();
    exit;
}
if ($idlist!='' || $folderlist!='') {
    //State 2.2/2.3

    //work out if we are in a download or export type (download must not write headers)
    $exportmen = new refworks_exportrefs();
    $exportmen->initexport();
    $submitted = $exportmen->return_submitted();
    $submittedtype=$exportmen->return_format_export_type($submitted);

    //Get reference data
    refworks_connect::require_login();
    if (!refworks_connect::$connectok) {
        //refworks connection error
        if ($submittedtype==refworks_exportrefs::EXPORT) {
            refworks_exportrefs_writeheader();
            refworks_base::write_footer();
        }
        exit;
    }

    //get all the ids for refs and folders
    $allids = refworks_ref_api::merge_id_list($idlist, refworks_base::return_foldername($folderlist));
    if (count($allids) == 0) {
        //NOTHING TO EXPORT!
        refworks_exportrefs_writeheader();
        refworks_base::write_error(get_string('refexport_error_noneselected','local_references'));
        refworks_base::write_footer();
        exit;
    }


    //now go and get the xml for the ref ids from api
    $data = refworks_ref_api::get_ref_data('&id='.implode('&id=', $allids));

    $options=new stdClass();
    $options->links=array();
    $options->styles='';
    if (refworks_base::$isinstance) {
        $options->filename=refworks_base::$instance->name;
        GLOBAL $COURSE;
        if (isset($COURSE->shortname)) {
            $options->coursename=$COURSE->shortname;
        } else {
            $options->coursename='';
        }
    } else {
        $options->filename=get_string('refworks:export','refworks');
        $options->coursename='';
    }
    $options->redirect=refworks_base::return_link('exportrefs.php');

    //print header if export type as there may be user interaction
    if ($submittedtype==refworks_exportrefs::EXPORT) {
        refworks_exportrefs_writeheader();

        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad');
        }

        refworks_base::write_sidemenu();

        //Main content goes here

        refworks_base::write_heading(get_string('export_ref', 'refworks'));
        refworks_connect::write_login_errors();
    }
    //log an export
    //if (refworks_base::$isinstance) {
    //    add_to_log(refworks_base::$course->id,'refworks','exportref','exportrefs.php?id='.refworks_base::$cm->id.'&all='.$showall,$submitted,refworks_base::$cm->id);
    //}

    //call export function
    if ($exportmen->start_export_action($submitted,$data,$options)===false) {
        print_error('refexport_error','local_references',refworks_base::return_link('exportrefs.php'));
    }

    //print footer as an export fucntion
    if ($submittedtype==refworks_exportrefs::EXPORT) {
        refworks_base::write_footer();
    }
    exit;
}

//Default view (State 1)

refworks_exportrefs_writeheader();
//init rwapi
refworks_connect::require_login();
refworks_base::write_sidemenu();

//Main content goes here

refworks_base::write_heading(get_string('export_ref', 'refworks'));

//check capability
if (!refworks_base::check_capabilities('mod/refworks:export')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('export_ref','refworks')));
    refworks_base::write_footer();
    exit;
}

refworks_connect::write_login_errors();

?>
<div class="instructions">
<p>On this screen you can select which references you wish to export. There are a number of export formats available and you can use them to:</p>
<ul><li>move your references to another bibliographic management tool</li>
<li>share your references with other people within a collaborative activity like a forum, wiki or blog.</li></ul>

<p>To select a reference for export select the checkbox next to it.
<?php
    if (refworks_base::check_capabilities('mod/refworks:folders')) {
        echo 'You can also select any of the folders in your library. If a folder is selected, then all references in that folder will be included in the export.';
    }
?>
 Once you are happy with your selection you can start the export process by selecting the <em>Export references</em> button.
</p>
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

$numperpage = 20;

//main form so checkboxes are picked up on submit
$output = '<form id="ref_sel_form" action="" method="post">';
$output .= '<div style="display: none;">';
$output .= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
if (refworks_base::$isinstance) {
    //if in instance also need id to be sent
    $output .= '<input type="hidden" name="id" value="'.refworks_base::$cm->id.'"/>';
}
$output .= '<input name="selected" type="hidden" value="'.$selrefs.'" />';
$output .= '<input name="selectedfl" type="hidden" value="'.$selfolders.'" />';
$output .= '</div>';

//create submit button
$output .= '<input name="export" type="submit" value="'.get_string('export_ref','refworks').'"/>';
$output .= '<p><label for="allrefs">'.get_string('selectallrefs','refworks').' </label><input name="allrefs" type="checkbox" value="allrefs" /></p>';
echo($output);

refworks_display::write_folder_list(refworks_folder_api::$folders, refworks_display::selectaction);

//call display class to create pagination headings
$curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('exportrefs.php?&amp;sort='.$sorting), $numperpage, false);

if ($showall) {
    $numperpage = $count;
}
//call refworks retrieve all with page num + amount per page + sorting
$allrefs = refworks_ref_api::get_all_refs($curpage, $numperpage, $sorting, 0);

//send refs returned to display class to create items
refworks_display::write_ref_list($allrefs, refworks_display::selectaction);

echo('</form>');//end of main ref_sel_form

//if (refworks_base::$isinstance) {
//    add_to_log(refworks_base::$course->id,'refworks','view','exportrefs.php?id='.refworks_base::$cm->id.'&all='.$showall,'View references for export',refworks_base::$cm->id);
//}
$curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('exportrefs.php?&amp;sort='.$sorting), $numperpage, false);
refworks_base::write_footer();


//Functions used in multiple states
function refworks_exportrefs_writeheader() {
    $curbreadcrumb = array(array('name' => get_string('export_ref', 'refworks'), 'type' => 'refworks'));

    refworks_base::write_header($curbreadcrumb);
}



?>