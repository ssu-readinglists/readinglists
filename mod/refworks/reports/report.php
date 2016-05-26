<?php
/**
 * Run available reports
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks/reports
 */

require_once(dirname(__FILE__).'/../refworks_base.php');
require_once(dirname(__FILE__).'/../refworks_ref_api.php');
require_once(dirname(__FILE__).'/../refworks_display.php');

refworks_base::init();
//Add reports here, the class must extend refworks_report
require_once(dirname(__FILE__).'/report_abstract.php');
require_once(dirname(__FILE__).'/report_linkcheck.php');
require_once(dirname(__FILE__).'/report_preview.php');



//get if optional sorting param set by user
$sorting = optional_param('sort',9,PARAM_INT);
//see what page we are on or if we are all (from optional params)
$showall = optional_param('all',false,PARAM_BOOL);
$curpage = optional_param('page',1,PARAM_INT);
//checkbox ids user selected
$selrefs = optional_param('selected','',PARAM_SEQUENCE);
$selfolders = optional_param('selectedfl','',PARAM_TEXT);
$selallrefs = optional_param('allrefs','',PARAM_TEXT);

$folderlist = optional_param('folderlist','',PARAM_SEQUENCE);

//write header
$curbreadcrumb = array(array('name' => get_string('reports', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb, '/reports/report.php');

refworks_connect::require_login();
refworks_base::write_sidemenu('reports/report.php');
refworks_base::write_heading(get_string('reports', 'refworks'));

//check capability
if (!refworks_base::check_capabilities('mod/refworks:runreport')) {
    refworks_base::write_error(get_string('nopermissions','error',get_string('refworks:runreport','refworks')));
    refworks_base::write_footer();
    exit;
}

//init rwapi
refworks_connect::write_login_errors();

$reporttorun = 'none';
//work out if a valid report run by user
foreach ($_POST as $param=>$val) {
    if (strpos($param,'report_')===0) {
        if (class_exists('refworks_'.$param)) {
            $reporttorun = $param;
        }
    }
}

if ($reporttorun!='none') {
    //State 2

    //check session key if form actioned
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }

    //init rwapi
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
    } else {
        //If selected is empty - user may not have js so check POST vars for any checkbox elements (ref_ or fol_)
        foreach ($_POST as $param => $val) {
            if (strpos($param,'r_')===0 && $val==1) {
                if ($selectedrefs!='') {
                    $selectedrefs .= ','.substr($param,2);
                } else {
                    $selectedrefs = substr($param,2);
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
        refworks_base::write_error(get_string('createlib_error_noneselected','refworks'));
        refworks_base::write_footer();
        exit;
    }
    $pieces = refworks_ref_api::merge_id_list($selectedrefs,refworks_base::return_foldername($folderlist, true));

    //remove any empty elements
    $pieces = array_filter($pieces);

    //get reference xml string from api to send to report
    $idstring = '&id='.implode('&id=',$pieces);
    $refdata = refworks_ref_api::get_ref_data($idstring);

    //run report - add require at top for each report you wish to support (class must be refworks_report_* with method run_report)
    call_user_func('refworks_'.$reporttorun.'::run_report',$refdata);

} else {//Default view
    //Main content goes here
    ?>
<div class="instructions">
<p>Reports can be run on selected references in this account. First select the required references, then any options from those available for the chosen report. Then select the button associated with the report to run it.</p>
<p>Please note that the performance of a report may be related to the number of references included, so including a large number of references (100+) is not recommended.</p>
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

    //report options form

    //add the reference style options to the user select
    $stylenamearray = array();//styles available
    $stylesarray = refworks_ref_api::get_reference_styles(); //get the styles available
    foreach ($stylesarray as $styleindex) {
        $stylenamearray[$styleindex['string']] = htmlspecialchars($styleindex['quikbib']);
    }
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

    //create submit buttons (submit buttons for each report should be called the name of class - which must be report_*)
    $output .= '<div id="report_reportlist">';
    //check links report
    $output .= '<fieldset><legend>'.get_string('report_linkcheck','refworks').'</legend>';
    $output .= '<label for="report_linkcheck"> '.get_string('report_linkcheck_desc','refworks').'</label><input name="report_linkcheck" type="submit" value="'.get_string('report_linkcheck','refworks').'"/>';
    $output .= '</fieldset>';
    //preview report
    $output .= '<fieldset><legend>'.get_string('report_preview','refworks').'</legend>';
    $output .= '<label for="report_preview">'.get_string('report_preview_desc','refworks').'</label>';
    $output .= '<label for="report_type">'.get_string('report_preview_type','refworks').'</label>';
    $output .= '<input type="radio" name="preview_type" value="preview_rp" checked="true"/>'.get_string('report_preview_resourcepage','refworks');
    $output .= '<input type="radio" name="preview_type" value="preview_sc"/>'.get_string('report_preview_sc','refworks');
    $output .= '<input type="radio" name="preview_type" value="preview_ca"/>'.get_string('report_preview_collaborative','refworks');
    $output .= '<label for="refstyleselect">'.get_string('output_style','refworks').'</label> '.html_writer::select($stylenamearray,'refstyleselect');
    $output .= '<p><input name="report_preview" type="submit" value="'.get_string('report_preview','refworks').'"/></p>';
    $output .= '</fieldset>';

    $output .= '</div>';

    //main form so checkboxes are picked up on submit

    $output .= '<p><label for="allrefs">'.get_string('selectallrefs','refworks').' </label><input name="allrefs" type="checkbox" value="allrefs" /></p>';
    echo($output);

    refworks_display::write_folder_list(refworks_folder_api::$folders,  refworks_display::selectaction);

    //call display class to create pagination headings
    $curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('reports/report.php?&amp;sort='.$sorting), $numperpage, false);

    if ($showall) {
        $numperpage = $count;
    }
    //call refworks retrieve all with page num + amount per page + sorting
    $allrefs = refworks_ref_api::get_all_refs($curpage, $numperpage, $sorting, 0);

    //send refs returned to display class to create items
    refworks_display::write_ref_list($allrefs, refworks_display::selectaction);

    echo('</form>');//end of main ref_sel_form

    //if (refworks_base::$isinstance) {
    //    add_to_log(refworks_base::$course->id,'refworks','view','reports/report.php?id='.refworks_base::$cm->id.'&all='.$showall,'Make a reference report',refworks_base::$cm->id);
    //}
    $curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('reports/report.php?&amp;sort='.$sorting), $numperpage, false);
}
refworks_base::write_footer();
?>