<?php
/**
 * Displays a bibliography. Output style options from _ref_api.php.
 *
 * @copyright &copy; 2009 The Open University
 * @author j.ackland-snow@open.ac.uk
 * @author j.platts@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/refworks_ref_api.php');
require_once(dirname(__FILE__).'/refworks_display.php');
require_once(dirname(__FILE__).'/refworks_portfolio.php');

refworks_base::init();
//get if optional citation style set by user
$returnedstyle = optional_param('refstyleselect','',PARAM_TEXT);

//get if optional sorting param set by user
$sorting = optional_param('sort',0,PARAM_INT);
//see what page we are on or if we are all (from optional params)
$showall = optional_param('all',false,PARAM_BOOL);
$curpage = optional_param('page',1,PARAM_INT);
//checkbox ids user selected
$selrefs = optional_param('selected','',PARAM_SEQUENCE);
$selfolders = optional_param('selectedfl','',PARAM_TEXT);
$selallrefs = optional_param('allrefs','',PARAM_TEXT);
//If submitted form via createbib button (state 2.1)
$selcreatebib = optional_param('createbib','no',PARAM_TEXT);

$folderlist = optional_param('folderlist','',PARAM_SEQUENCE);
//write header
$curbreadcrumb = array(array('name' => get_string('create_bib', 'refworks'), 'type' => 'refworks'));
refworks_base::write_header($curbreadcrumb);
//check session key if form actioned
if ($selcreatebib!='no') {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad');
    }
}
refworks_connect::require_login();
refworks_base::write_sidemenu();
refworks_base::write_heading(get_string('create_bib', 'refworks'));

if ($selcreatebib!='no') {
    //State 2
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

    //creation of bibliography

    if ($citations = refworks_ref_api::get_citations($pieces, $returnedstyle)) {
        $citations_display = refworks_display::write_citation_list($citations);
    } else {
        refworks_base::write_error(get_string('get_citations_error','refworks'));
        refworks_base::write_footer();
        exit;
    }


    //display cites
    echo($citations_display);

    echo '<hr />'; // border between citations and formattted references

    //reference list
    //remove extraneous information from beginning of reference list
    $citations = refworks_display::write_formatted_reference_list($citations);
    // remove unwanted styling then display formatted references
    $prelim = strip_tags($citations,'<p><em><strong><u><div>');
    echo('<div class="citation_list">'.$prelim.'');

    echo '</div>';

    if (refworks_base::$isinstance) {
        add_to_log(refworks_base::$course->id,'refworks','createbib','createbib.php?id='.refworks_base::$cm->id.'&all='.$showall,'Created Bibliography',refworks_base::$cm->id);
    }
}
else{//Default view
    //Main content goes here
    //check capability
    if (!refworks_base::check_capabilities('mod/refworks:bibliography')) {
        refworks_base::write_error(get_string('nopermissions','error',get_string('create_bib','refworks')));
        refworks_base::write_footer();
        exit;
    }

    //init rwapi
    refworks_connect::write_login_errors();

    ?>
	<div class="instructions">
	<p>On this screen you can select which references you wish to include in a bibliography.</p>
	<p>The bibliography can be generated using a number of different output (referencing) styles. Select the referencing style you wish to use from the <em>Output style</em> drop-down menu. If you are unsure as to the correct output style to use, it is recommended that you use the default style.</p>
	<p>
	Selecting the <em>Create bibliography</em> button will generate the bibliography and display it on screen. Both citations to be used within the text of a document (ordered by your selection) and a bibliography (ordered depending on the output style) are displayed. You can cut and paste the displayed text to re-use in a document for assignments etc.
	</p>
	<p>
	Always check the generated bibliography for general accuracy and against any specific referencing guidelines in your course guide.
	</p>
	<p>
    <strong>For more help check the examples on <a href="[local data - link to referencing examples]" target="_blank">[local data - text for link]</a></strong>
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

    //add the reference style options to the user select
    $stylesarray = refworks_ref_api::get_reference_styles(); //get the styles available
    foreach ($stylesarray as $styleindex) {
		$stylenamearray[$styleindex['string']] = $styleindex['quikbib'];
    }
    //main form so checkboxes are picked up on submit
    $output = '<form id="ref_sel_form" action="" method="post">';
    $output .= '<label for="menusort">'.get_string('output_style','refworks').'</label> '.html_writer::select($stylenamearray, 'refstyleselect', $returnedstyle, '');
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
    $output .= ' <input name="createbib" type="submit" value="'.get_string('create_bib','refworks').'"/>';
    $output .= '<p><label for="allrefs">'.get_string('selectallrefs','refworks').' </label><input name="allrefs" type="checkbox" value="allrefs" /></p>';
    echo($output);

    refworks_display::write_folder_list(refworks_folder_api::$folders,  refworks_display::selectaction);

    //call display class to create pagination headings
    $curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('createbib.php?&amp;sort='.$sorting), $numperpage, false);

    if ($showall) {
        $numperpage = $count;
    }
    //call refworks retrieve all with page num + amount per page + sorting
    $allrefs = refworks_ref_api::get_all_refs($curpage, $numperpage, $sorting, 0);

    //send refs returned to display class to create items
    refworks_display::write_ref_list($allrefs, refworks_display::selectaction);

    echo('</form>');//end of main ref_sel_form

    if (refworks_base::$isinstance) {
        add_to_log(refworks_base::$course->id,'refworks','view','createbib.php?id='.refworks_base::$cm->id.'&all='.$showall,'View references for Bibliography',refworks_base::$cm->id);
    }
	$curpage = refworks_display::write_page_list($sorting, $curpage, $count, $showall, refworks_base::return_link('createbib.php?&amp;sort='.$sorting), $numperpage, false);
}

refworks_base::write_footer();
?>