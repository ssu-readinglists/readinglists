<?php
/**
 * Display functions for generic functionality
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/../../local/references/linking.php');

class refworks_display {

    const noaction = 0;
    const viewaction = 1;
    const selectaction = 2;
    const folderviewaction = 3;
    const managefoldersaction = 4;
    const sharefoldersaction = 5;
    const viewdormantaction = 6;
    const refshareprefix = 'http://www.refworks.com/refshare?';
    const rsssuffix = '&amp;rss';

    private static $imagepaths;

    /**
     * Retuns an object of all the standard image paths that are used in the module for easy reference
     */
    public static function get_image_paths() {
        if (isset(self::$imagepaths)) {
            return self::$imagepaths;
        }
        global $OUTPUT;

        self::$imagepaths = new stdClass();
        self::$imagepaths->delete = $OUTPUT->pix_url('t/delete');
        self::$imagepaths->edit = $OUTPUT->pix_url('t/edit');
        self::$imagepaths->folder = $OUTPUT->pix_url('f/folder');
        self::$imagepaths->restore = $OUTPUT->pix_url('t/restore');
        self::$imagepaths->delfolder = $OUTPUT->pix_url('delfolder', 'mod_refworks');
        self::$imagepaths->group = $OUTPUT->pix_url('group', 'mod_refworks');
        self::$imagepaths->source = $OUTPUT->pix_url('source_16', 'local_references');
        self::$imagepaths->rss = $OUTPUT->pix_url('i/rss');
        return self::$imagepaths;
    }

    /**
     * Writes heading buttons for a pagination list
     * Returns the current page (this will be changed from sent if invalid value sent)
     * @param $sorting Int:Sorting option selected
     * @param $curpage Int:Number of the current page
     * @param $count Int: Total number of items
     * @param $showall Bool: Show all instead of each page
     * @param $url String: Base url for page
     * @param $numitemsmax Int: Maximum items to display per page
     * @return Int: Current page
     */
    public static function write_page_list($sorting, $curpage, $count, $showall, $url, $numitemsmax = 20, $sortform = true) {
        return refworks_base::$renderer->write_page_list($sorting, $curpage, $count, $showall, $url, $numitemsmax, $sortform);
    }

    //CALLED BY RENDERER
    public static function write_page_list_renderer($sorting, $curpage, $count, $showall, $url, $numitemsmax = 20, $sortform = true) {
        if ($count==0) {
            //if no references to work with - quit, don't show
            return 0;
        }

        $html ='<div id="topopt">';

        //work out if there are any params in the url that need to go in our form
        $params = array();
        $paramstr = substr($url,strpos($url, '?')+1);
        $paramar = explode('&amp;',$paramstr);
        for ($a=0,$max=count($paramar);$a<$max;$a++) {
            if (strpos($paramar[$a],'sort=')===false && strpos($paramar[$a],'id=')===false && $paramar[$a]!='') {
                $paramsplit = explode('=', $paramar[$a]);
                $params[$paramsplit[0]] = $paramsplit[1];
            }
        }

        //setup sorting drop-down
        //9    Last Modified Date Time, descending
        //8    Creation Date Time, descending
        //0    Authors, Primary
        //5    Title, Primary
        $sort_options=array(9=>get_string('sort_last_modified','refworks'),8=>get_string('sort_creation','refworks'),0=>get_string('sort_author','refworks'),5=>get_string('sort_title','refworks'));
        //Sort drop-down (with button in case no js support)
        //On pages where you can select don't show form so form with checkboxes gets selected instead
        if ($sortform) {
            //work out core file from url if it has params
            $action = $url;
            if (strpos($url, '?')) {
                $action = substr($url,0,strpos($url, '?'));
            }
            $html .= '<form id="sorting" method="get" action="'.$action.'">';
        }
        $html .= '<label for="menusort">'.get_string('sort_by','refworks').'</label>';
        $html .= html_writer::select($sort_options, 'sort', $sorting, '');
        //$html .= choose_from_menu($sort_options, 'sort', $sorting, '', '','0',true);
        $html .= '<input type="submit" value="'.get_string('sort_by_button','refworks').'" />';
        if (refworks_base::$isinstance) {
            //if in instance also need id to be sent
            $html .= '<input type="hidden" name="id" value="'.refworks_base::$cm->id.'" />';
        }
        //keep if they have selected all (otherwise will default to page 1 on sort change)
        if ($showall) {
            $html .= '<input type="hidden" name="all" value="true" />';
        }
        //any other params (picked up from $url, excludes sort)
        foreach ($params as $key=>$val) {
            $html .= '<input type="hidden" name="'.$key.'" value="'.$val.'" />';
        }
        if ($sortform) {
            $html .= '</form>';
        }

        //work out total number of pages, based on count and numitemsmax
        $totpages = (int)ceil($count / $numitemsmax);
        //if showall is true, then curpage is set to 1
        if ($showall) {
            $curpage = 1;
        } else {
            //check curpage, if greater than total then set to last instead
            if ($curpage >  $totpages) {
                $curpage = $totpages;
            }
        }
        $html .= '<div id="pagelist">';
        //create all link (if showall make active)
        $html .= '<span class="pagelist_all"><a href="'.$url.'&amp;all=true"';
        if ($showall) {
            $html .= ' class="pagelist_selected"';
        }
        $html .= '>'.get_string('pagelist_all','refworks').'</a></span>';
        //Create pages (make curpage active)
        $html .= '<span class="pagelist_pages">'.get_string('pagelist_page','refworks');
        for ($a=0; $a<$totpages; $a++) {
            //make button for each page
            $html .= '<a href="'.$url.'&amp;page='.($a+1).'"';
            if ($a+1 == $curpage && !$showall) {
                $html .= ' class="pagelist_selected"';
            }
            $html .= ' title="'.get_string('pagelist_page','refworks').' '.($a+1).'">'.($a+1).'</a>';
        }

        $html .= '</span>';
        //Make next previous buttons, work out if available
        $html .= '<span class="pagelist_pageflip">';
        if ($curpage>1 && !$showall) {
            $html .= '<a href="'.$url.'&amp;page='.($curpage-1).'" title="'.get_string('pagelist_previous','refworks').' '.get_string('pagelist_page','refworks').'">';
        }
        $html.=get_string('pagelist_previous','refworks');
        if ($curpage>1 && !$showall) {
            $html .= '</a>';
        }
        $html .= '</span>';
        $html .= '<span class="pagelist_pageflip">';
        if ($curpage<$totpages && !$showall) {
            $html .= '<a href="'.$url.'&amp;page='.($curpage+1).'" title="'.get_string('pagelist_next','refworks').' '.get_string('pagelist_page','refworks').'">';
        }
        $html.=get_string('pagelist_next','refworks');
        if ($curpage<$totpages && !$showall) {
            $html .= '</a>';
        }
        $html .= '</span>';
        //write
        $html .= '</div>';//end pagelist
        $html .= '</div>';//end topopt
        echo($html);
        //return
        return $curpage;
    }

    /**
     * Writes a list of shared account items to the page
     * Optional second column can display different types of interaction
     * @param $accountsarray array: shared account array
     * @param $actiontype CONSTANT: Type of action (CONSTANT from _display class)
     * @return
     */
     public static function write_shared_accounts_list($accountsarray, $actiontype = self::noaction) {
         refworks_base::$renderer->write_shared_accounts_list($accountsarray, $actiontype);
     }

     public static function write_shared_accounts_list_renderer($accountsarray, $actiontype = self::noaction) {
        if (!is_array($accountsarray)) {
            return;
        }
        /*
        if ($actiontype==self::noaction) {
            //$output= '<div><a href="collab_manage.php?showdormant=1">Manage dormant accounts</a></div>';
            $output= '<div><a href="#" onclick="javascript:return showDormantAccounts();">Manage dormant accounts</a></div>';
            echo $output;
            return;
        }
        */
        if ($actiontype==self::viewaction) {
            $output = '<div>';
            $output.= '<table id="sharedaccountstable" class="sharedaccountstable" cellspacing="0" summary="list of shared accounts">'.
            '<caption align="top">list of shared accounts</caption>'.
            '<thead><tr><th scope="col">Account name</th>'.
            '<th scope="col">Created</th>'.
            '<th scope="col">Last login</th>'.
            '<th scope="col">Action</th></tr></thead><tbody>';
        }
        if ($actiontype==self::viewdormantaction) {
            $output= '<div id="view_dormant_switch"><a href="#" onclick="javascript:showDormantAccounts(); return"><span id="dormant_toggle_label">Show dormant accounts</span></a></div>';
            $output.= '<div id="dormant_accounts"><table id="dormant_accounts_table" cellspacing="0" summary="list of dormant accounts">'.
            '<caption align="top">list of dormant accounts</caption>'.
            '<thead><tr><th scope="col">Account name</th><th scope="col">Creator</th>'.
            '<th scope="col">Action buttons</th></tr></thead><tbody>';
        }
        foreach ($accountsarray as $accountarray) {

            if ($actiontype==self::viewaction || $actiontype==self::viewdormantaction) {
                $output.='<tr>';
            }

            switch($actiontype) {
                case self::viewaction:
                    $output .= self::create_shared_account_opts($accountarray);
                break;
                case self::viewdormantaction:
                    $output .= self::create_shared_dormant_account_opts($accountarray);
                break;
                default:;
            }
            if ($actiontype==self::viewaction || $actiontype==self::viewdormantaction) {
                $output.='</tr>';
            }

        }
        if ($actiontype==self::viewaction || $actiontype==self::viewdormantaction) {
            $output.= '</tbody></table></div>';
        }
        echo $output;
    }

   /**
     * Writes a list of folder items to the page
     * Optional second column can display different types of interaction
     * @param $folderarray array: folder names array (usually from _folder_api class)
     * @param $actiontype CONSTANT: Type of action (CONSTANT from _display class)
     * @param $sharedfolderarray array: shared folder names array
     * @return
     */
    public static function write_folder_list($folderarray, $actiontype = self::noaction, $sharedfolderarray = array()) {
        refworks_base::$renderer->write_folder_list($folderarray, $actiontype, $sharedfolderarray);
    }
    public static function write_folder_list_renderer($folderarray, $actiontype = self::noaction, $sharedfolderarray = array()) {
        if (!is_array($folderarray)) {
            return;
        }
        $output = '<div class="folder_list">';


        $output .='<table summary="list of folders" cellspacing="0">'.
        '<caption align="top">list of folders</caption>'.
        '<thead><tr><th scope="col">foldername</th>';
        //setup other column headings depending on action
        switch($actiontype) {
            case self::selectaction:
                $output .= '<th scope="col">select folder</th>';
                break;
            case self::managefoldersaction:
                $output .= '<th scope="col">folder actions</th>';
                break;
            case self::sharefoldersaction:
                $output .= '<th scope="col">Link and RSS feed (if shared)</th><th scope="col">Action button</th>';
            break;
        }
        $output .='</tr></thead><tbody>';
        for ($a=0, $max=count($folderarray); $a<$max; $a++) {
            //setup container
            $output .= '<tr>';
            //create folder
            $output .= '<td scope="row"><div class="foldercontainer_item">';
            $output .= htmlspecialchars(refworks_base::return_foldername($folderarray[$a]['name'],true)).' ('.$folderarray[$a]['numrefs'].')';
            $output .= '</div></td>';
            //action column?
            switch($actiontype) {
                case self::selectaction:
                    $output .= '<td><div class="foldercontainer_sel">';
                    //work out if already selected
                    $issel = false;
                    $fldname = 'fl_'.str_replace(' ', '~~', $folderarray[$a]['name']);
                    //remove any square brackets as posting with these invokes array
                    $fldname = str_replace('[', '``', $fldname);
                    $fldname = str_replace(']', '¬¬', $fldname);
                    //selected vals should always be in selected field
                    $selflds = optional_param('selectedfl','',PARAM_TEXT);
                    if ($selflds!='') {
                        $selarray = explode('@@',$selflds);
                        if (in_array($fldname, $selarray)) {
                            $issel = true;
                        }
                    } else {
                        //check if submit select set (i.e. no JS support)
                        $issel = optional_param($fldname,false,PARAM_BOOL);
                    }

                    $output .= print_checkbox(htmlspecialchars(refworks_base::return_foldername($fldname)), true, $issel, '', get_string('folder_select','refworks').' '.htmlspecialchars(refworks_base::return_foldername($folderarray[$a]['name'],true)), '' , true);
                    $output .= '</div></td>';
                    break;
                /////////////////////
                case self::managefoldersaction:
                    $issel = false;
                    $fldname = 'fl_'.str_replace(' ', '~~', $folderarray[$a]['name']);
                    $output .= '<td>';
                    $output .= self::create_folder_opts($folderarray[$a]['name'],$a); //create folder options method required??
                    $output .= '</td>';
                    break;
                 case self::sharefoldersaction:
                    $issel = false;
                    $fldname = 'fl_'.str_replace(' ', '~~', $folderarray[$a]['name']);
                    //$output .= '<div>';
                    $foundflag = false;
                    foreach ($sharedfolderarray as $sharedfolderarray_item) {
                        if ($folderarray[$a]['name']==$sharedfolderarray_item['folder']) {
                            $output .= self::share_folder_opts($folderarray[$a]['name'],$a, true, $sharedfolderarray_item['shareurlpath']); //already shared
                            $foundflag = true;
                        }
                    }
                    if ($foundflag==false) {
                        $output .= self::share_folder_opts($folderarray[$a]['name'],$a); //not already shared
                    }
                    break;
                //////////////////////
            }
            $output .= '</tr>';//end container
        }

        $output .= '</tbody></table></div>';//end folder_list
        echo $output;
    }

    /**
     * Returns a list of Citation items
     * @param $xmlstring string:XML string
     * @return $output
     */
    public static function write_citation_list($xmlstring) {
        return refworks_base::$renderer->write_citation_list($xmlstring);
    }
    public static function write_citation_list_renderer($xmlstring) {
        $citxml = new domDocument();
        $citxml->loadXML($xmlstring);
        $cits = $citxml->getElementsByTagName('Formated');
        $output = '<div class="citation_list">';
        for ($a=0, $max=$cits->length; $a<$max; $a++) {
            //setup container
            if ($cits->item($a)->nodeValue!='()') {
                $output .= '<p>';
                //create item
                $output .= ltrim($cits->item($a)->nodeValue);
                //end container
                $output .= '</p>';
            }
        }
        $output.= '</div>';
        $originals = array("<I>","</I>","<B>","<b>","</B>","</b>");
        $replacements = array("<em>","</em>","<strong>","<strong>","</strong>","</strong>");
        $newoutput = str_replace($originals,$replacements,$output);
        $newoutput = strip_tags($newoutput,'<p><em><strong><u><i><b><span><div>');
        return $newoutput;
    }

    /**
     * Returns a list of Formatted Reference items
     * @param $xmlstring string:XML string
     * @return $newoutput
     */
    public static function write_formatted_reference_list($xmlstring) {
        return refworks_base::$renderer->write_formatted_reference_list($xmlstring);
    }
    public static function write_formatted_reference_list_renderer($xmlstring) {
        $citxml = new domDocument();
        $citxml->loadXML($xmlstring);
        $cits = $citxml->getElementsByTagName('FormattedBib');
        $output = '<div class="formatted_references">';
        for ($a=0, $max=$cits->length; $a<$max; $a++) {
            //setup container
            $output .= $cits->item($a)->nodeValue;
        }
        $output.= '</div>';
        //remove some unneccessary stuff at the beginning
        $centrealignpos = strripos($output,'<p align="center">');
        if ($centrealignpos!==false) {
            $output = substr($output,($centrealignpos+18));
        }
        //replace tags
        $originals = array("<I>","</I>","<B>","<b>","</B>","</b>");
        $replacements = array("<em>","</em>","<strong>","<strong>","</strong>","</strong>");
        $newoutput = str_replace($originals,$replacements,$output);

        //remove more supefluous stuff
        $processedstring = preg_replace("/(<p style) {1,}[^>]+>{1,}/","<p>",$newoutput);
        $processedstring = preg_replace("/(<p>)+/","</p><p>",$processedstring);
        if ($fromfirstp = strstr($processedstring,'</p>')) {
            $processedstring = substr($fromfirstp,4);
        }
        $posoflastp = strrpos($processedstring,'<p>');
        if ($posoflastp!==false) {
            $processedstring = substr($processedstring,0,($posoflastp));
        }
        //bit of a hack to remove xtra spaces in mla
        $processedstring = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;','',$processedstring);
        return $processedstring;
    }


    /**
     * Writes a list of Reference items to the page
     * Optional second column can display different types of interaction
     * @param $xmlstring string:XML string with <reference> tags
     * @param $actiontype CONSTANT: Type of action (CONSTANT from _display class)
     * @return
     */
    public static function write_ref_list($xmlstring, $actiontype = self::noaction) {
        refworks_base::$renderer->write_ref_list($xmlstring, $actiontype);
    }
    public static function write_ref_list_renderer($xmlstring, $actiontype = self::noaction) {
        if (!strpos($xmlstring, '<reference')) {
            return false;
        }
        $xml = new domDocument();
        $xml->loadXML($xmlstring);

        $linksarray = linking::create_link($xml, 'MyReferences');

        $refs = $xml->getElementsByTagName('reference');

        $output = '<div class="ref_list">';

        //setup table for reference list
        $output .='<table summary="list of reference items" cellspacing="0">'.
        '<caption align="top">list of reference items</caption>'.
        '<thead><tr><th scope="col">Reference details</th>';
        //work out if central column required
        if ($actiontype==self::viewaction) {
            if (refworks_base::check_capabilities('mod/refworks:folders')) {
                $output .= '<th scope="col">In folder</th>';
            }
        }
        if ($actiontype == self::viewaction || $actiontype == self::folderviewaction) {
            $output .= '<th scope="col">Reference actions</th>';
        }else if ($actiontype == self::selectaction) {
            $output .= '<th scope="col">Reference selection</th>';
        }

        //end table headers
        $output .='</tr></thead><tbody>';

        for ($a=0, $max=$refs->length; $a<$max; $a++) {
            //setup container
            $output .= '<tr>';
            //create item
            $output .= self::create_ref_item($refs->item($a), $actiontype);
            //action column?
            switch($actiontype) {
                case self::viewaction:
                    $output .= self::create_ref_folderdisplay($refs->item($a));
                    $output .= self::create_ref_opts($refs->item($a),$linksarray[$a]);
                    break;
                case self::folderviewaction:
                    //get folder name (must be within folder param in page)
                    $fldname = optional_param('folder','',PARAM_TEXT);
                    $fldname = refworks_base::return_foldername($fldname);
                    $output .= self::create_ref_opts($refs->item($a),$linksarray[$a],$fldname);
                    break;
                case self::selectaction:
                    $output .= self::create_ref_select($refs->item($a));
                    break;
            }
            //end container
            $output .= '</tr>';
        }
        $output .= '</tbody></table></div>';
        echo $output;
    }

    private static function create_ref_item($refnode, $actiontype) {
        return refworks_base::$renderer->create_ref_item($refnode, $actiontype);
    }
    public static function create_ref_item_renderer($refnode, $actiontype) {
        //create container
        $output = '<th class="itemcontainer_item" scope="row">';
        //icon for type?

        //title

        $nodeval=self::get_element_value($refnode,'t1');
        $rt = self::get_element_value($refnode,'rt');
        if ($nodeval!==false || $nodeval=='') {
            //if empty?
            if ($nodeval == '') {
                //If periodical title has been set, use that instead of empty title
                $periodical = self::get_element_value($refnode,'jf');
                if ($periodical != '' && $periodical != false) {
                    $nodeval = $periodical;
                } else {
                    $nodeval = '&nbsp;';
                }
            }
            $t2 = self::get_element_value($refnode,'t2');
            if ($t2 && $rt=="Book, Section") {
                $nodeval .= " (in ".$t2.")";
            }
            if ($rt=="Journal Article") {
                $nodeval .= " (in ".self::get_element_value($refnode,'jf').")";
            }
            $output .= '<p class="itemcontainer_item_title">'.$nodeval.'</p>';
        } else {
            $output .= '<p class="itemcontainer_item_title">&nbsp;</p>';
        }
        //primary authors
        $nodelist = $refnode->getElementsByTagName('a1');
        $output .= '<p class="itemcontainer_item_author">';
        foreach ($nodelist as $node) {
            $output .= $node->nodeValue.'; ';
        }
        $output .= '</p>';

        //date, publisher, place of pub
        $output .= '<p class="itemcontainer_item_pub">';
        $date = self::get_element_value($refnode,'yr');
        $pub = self::get_element_value($refnode,'pb');
        $place = self::get_element_value($refnode,'pp');
        if ($date) {
            $output .= $date;
            if ($pub || $place) {
                $output .= ', ';
            }
        }
        if ($pub) {
            $output .= $pub;
            if ($place) {
                $output .= ', ';
            }
        }
        if ($place) {
            $output .= $place;
        }
        $output .= '</p>';

        $attachments = $refnode->getElementsByTagName('at');
        if ($attachments->length>0) {
            global $CFG, $OUTPUT;
            require_once($CFG->libdir.'/filelib.php');
            $output .= '<p class="itemcontainer_item_attachments">';
            foreach ($attachments as $filename) {
                //get filename
                $attach = $filename->nodeValue;
                $pix = $OUTPUT->pix_url('/f/'.mimeinfo('icon',$attach));
                $output .= '<span class="itemcontainer_item_attachment"><a href="managerefs.php?attachment='.urlencode($attach).'&amp;refid='.self::get_element_value($refnode,'id').'&amp;sesskey='.sesskey().'" title="'.get_string('attachment_alt','refworks',htmlspecialchars($attach)).'"><img src="'.$pix.'" alt="" />'.htmlspecialchars($attach).'</a> </span>';
            }
            $output .= '</p>';
        }

        //end container
        $output .= '</th>';

        return $output;
    }

    /**
     * Creates html string of shared account info and options
     * @param $accountarray array: shared account info array
     * @return string
     */
    private static function create_shared_account_opts($accountarray) {
        return refworks_base::$renderer->create_shared_account_opts($accountarray);
    }
    public static function create_shared_account_opts_renderer($accountarray) {
        global $CFG;

        $output = '';
        $hiddenoutput = '';
        foreach ($accountarray as $key=>$value) {
            switch($key) {
                case 'name':
                    $output .= '<td class="shared_account_opts shared_account_name" title="'.$accountarray->login.'||'.$accountarray->password.'">';
                    $name = stripslashes(htmlspecialchars($value));
                    $output .= stripslashes(htmlspecialchars($value));
                    $output .= '</td>';
                    $hiddenoutput .= '<input type="hidden" name="account_name"  class="shared_account_hidden" value="'.stripslashes(htmlspecialchars($value)).'" />';
                    break;
                case 'created':
                case 'lastlogin':
                    $output .= '<td class="shared_account_opts">';
                    if ($value!=null && $value!='') {
                        $output .= userdate($value,'%e/%m/%Y %R');
                    } else {
                        $output .= '&nbsp;';
                    }
                    $output .= '</td>';
                    break;
                case 'id':
                    $id = $value;
                    $hiddenoutput .= '<input type="hidden" name="account_id"  class="shared_account_hidden" value="'.$value.'" />';
                    break;
            }
        }
        $hiddenoutput .= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
        if (refworks_base::$isinstance) {
            $hiddenoutput .= '<input name="id" type="hidden" value="'.refworks_base::$cm->id.'" />';
        }
        $output .= '<td class="shared_account_action"><div class="shared_accounts_line_div"><a href="collab_manage_users.php?accid='.$id;
        if (refworks_base::$isinstance) {
            $output .= '&amp;id='.refworks_base::$cm->id;
        }
        $output .= '" title="Manage users ('.$name.')">Manage users</a><form name="manage_shared_account_'.$id.'" class="manage_form_class" action="collab_manage.php" method="post">'; //TODO
        $output .= '<input type="image" src="'.self::get_image_paths()->edit.'" value="Rename account" name="rename_account"   alt="Rename account ('.$name.')" title="Rename account ('.$name.')" />'; //TODO
        $output .= '<input type="image"  src="'.self::get_image_paths()->delete.'" value="Delete account" name="delete_account"   alt="Delete account ('.$name.')" title="Delete account ('.$name.')" />'.$hiddenoutput.'</form></div></td>'; //TODO
        return $output;
    }

    /**
     * Creates html string of shared account info and options
     * @param $accountarray array: shared account info array
     * @return string
     */
    private static function create_shared_dormant_account_opts($accountarray) {
        return refworks_base::$renderer->create_shared_dormant_account_opts($accountarray);
    }
    public static function create_shared_dormant_account_opts_renderer($accountarray) {
        global $CFG;

        $output = '';
        $hiddenoutput = '';
        foreach ($accountarray as $key=>$value) {
            switch($key) {
                case 'name':
                    $output .= '<td class="dormant_td_name">';
                    $name = stripslashes(htmlspecialchars($value));
                    $output .= stripslashes(htmlspecialchars($value));
                    $output .= '</td>';
                    $hiddenoutput .= '<input type="hidden" name="account_name"  class="shared_account_hidden" value="'.stripslashes(htmlspecialchars($value)).'" />';
                    break;
                case 'id':
                    $id = $value;
                    $hiddenoutput .= '<input type="hidden" name="account_id"  class="shared_account_hidden" value="'.$value.'" />';
                    break;
            }
        }
        $hiddenoutput .= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
        if (refworks_base::$isinstance) {
            $hiddenoutput .= '<input name="id" type="hidden" value="'.refworks_base::$cm->id.'" />';
        }
        //show if the account login records belonging to a particular user
        if (strpos($accountarray->login,'[[')!==false && strpos($accountarray->login,']]')!==false) {
            $name = substr($accountarray->login, strpos($accountarray->login,'[[')+2);
            $name = substr($name, 0, strpos($name,']]'));
            $output .= '<td>'.$name.'</td>';
        } else {
            $output .= '<td>&nbsp;</td>';
        }

        $output .= '<td class="dormat_td_action"><div><form name="manage_shared_account_'.$id.'" class="manage_form_class" action="collab_manage.php" method="post">';
        $output .= '<input type="image" name="delete" src="'.self::get_image_paths()->delete.'" alt="'.get_string('team_permanent_delete_account','refworks', $name).'" title=" '.get_string('team_permanent_delete_account','refworks', $name).'" />';
        $output .= '<input type="image" name="restore" src="'.self::get_image_paths()->restore.'" alt="'.get_string('team_restore_account','refworks', $name).'" title=" '.get_string('team_restore_account','refworks', $name).'" />'.$hiddenoutput.'</form></div></td>';



        return $output;
    }


    /**
     * Creates html string of reference option buttons
     * @param $refnode xmlNode: reference xml node
     * @param $link string: url linking to source
     * @param $folder bool: If in a folder display page
     * @return string
     */
    private static function create_ref_opts($refnode, $link, $folder='') {
        return refworks_base::$renderer->create_ref_opts($refnode, $link, $folder);
    }
    public static function create_ref_opts_renderer($refnode, $link, $folder='') {
        GLOBAl $CFG;
        $output = '<td class="itemcontainer_opts">';

        //get id of ref
        if ($id=self::get_element_value($refnode,'id')) {
            $reftitle = ' '.self::get_element_value($refnode,'t1');
            $reftitle = strip_tags($reftitle);

            if (refworks_base::check_capabilities('mod/refworks:folders')) {
                $fld = self::get_element_value($refnode,'fl');
                if ($folder=='') {
                    //show an add to folder button
                        $output .= '<a class="itemcontainer_opts_fld" href="'.refworks_base::return_link('moveref.php?rid='.$id).'"><img src="'.self::get_image_paths()->folder.'" alt="'.get_string('add_to_folder','refworks').$reftitle.'" title="'.get_string('add_to_folder','refworks').$reftitle.'"/></a>';
                } else {
                    //in a folder view, double check has folder
                    if ($fld) {
                        //show a remove from folder button if in folder
                        $output .= '<a class="itemcontainer_opts_fld" href="'.refworks_base::return_link('removeref.php?rid='.$id).'&amp;folder='.urlencode($folder).'"><img src="'.self::get_image_paths()->delfolder.'" alt="'.get_string('remove_from_folder','refworks').$reftitle.'" title="'.get_string('remove_from_folder','refworks').$reftitle.'"/></a>';
                    }
                }
            }

            //delete and update (need to be post form so secure as accessing account)
            $output .= '<form id="ref_updates_'.$id.'" method="post" action="managerefs.php">';


            //check delete capability
            if (refworks_base::check_capabilities('mod/refworks:delete')) {
                $output .= '<input type="image" name="delete" src="'.self::get_image_paths()->delete.'" alt="'.get_string('ref_delete','refworks').$reftitle.'" title="'.get_string('ref_delete','refworks').$reftitle.'" />';
            }
            if (refworks_base::check_capabilities('mod/refworks:update')) {
                $output .= '<input type="image" name="update" src="'.self::get_image_paths()->edit.'" alt="'.get_string('ref_update','refworks').$reftitle.'" title="'.get_string('ref_update','refworks').$reftitle.'" />';
            }

            $output .= '<div style="display: none;">';
            $output .= '<input type="hidden" name="refid" value="'.$id.'" />';
            $output .= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
            if (refworks_base::$isinstance) {
                //if in instance also need id to be sent
                $output .= '<input type="hidden" name="id" value="'.refworks_base::$cm->id.'" />';
            }
            $output .= '</div></form>';
        }

        //get link to source
        if ($link!='') {
			// Modified for SSU to open in a new window. owen@ostephens.com. 28th March 2012
            $output .= '<a target="_blank" href="'.$link.'"><img src="'.self::get_image_paths()->source.'" alt="'.get_string('ref_sourcelink','refworks').$reftitle.'" title="'.get_string('ref_sourcelink','refworks').$reftitle.'"/></a>';
        }

        return $output.'</td>';

    }

    /**
     * Creates html string of folder option buttons
     * @param $foldername string: folder name
     * @return string
     */
    private static function create_folder_opts($foldername, $num=0) {
        return refworks_base::$renderer->create_folder_opts($foldername, $num);
    }
    public static function create_folder_opts_renderer($foldername, $num=0) {
        GLOBAl $CFG;
        $output = '<div class="flditemcontainer_opts">';

        //delete and update (need to be post form so secure as accessing account)
        $output .= '<form id="ref_updates_'.$num.'" method="post" action="managefolders.php">';

        //check delete capability

        // image to delete folder and references contained within
        $output .= '<input type="image" name="delete" src="'.self::get_image_paths()->delete.'" alt="'.get_string('delete_refs_and_folder','refworks').' '.htmlspecialchars(refworks_base::return_foldername($foldername,true)).'" title=" '.get_string('delete_refs_and_folder','refworks').' '.htmlspecialchars(refworks_base::return_foldername($foldername,true)).'" />';
        // image to delete folder only
        $output .= '<input type="image" name="delete_folder" src="'.self::get_image_paths()->delfolder.'" alt=" '.get_string('remove_only_folder','refworks').' '.htmlspecialchars(refworks_base::return_foldername($foldername,true)).'" title="'.get_string('remove_only_folder','refworks').' '.htmlspecialchars(refworks_base::return_foldername($foldername,true)).'" />';

        // image to rename folder
        $output .= '<input type="image" name="update" src="'.self::get_image_paths()->edit.'" alt=" '.get_string('rename_folder','refworks').' '.htmlspecialchars(refworks_base::return_foldername($foldername,true)).'" title="'.get_string('rename_folder','refworks').' '.htmlspecialchars(refworks_base::return_foldername($foldername,true)).'" />';


        $output .= '<div style="display: none;">';
        $output .= '<input type="hidden" name="foldername" value="'.htmlspecialchars($foldername).'" />';
        $output .= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
        if (refworks_base::$isinstance) {
            //if in instance also need id to be sent
            $output .= '<input type="hidden" name="id" value="'.refworks_base::$cm->id.'" />';
        }
        $output .= '</div></form>';

        return $output.'</div>';

    }

    /**
     * Creates html string of share folder option buttons
     * @param $foldername string: folder name
     * @param $foldername string: folder name
     * @param $shared boolean: shared already or not
     * @param $shareurlpath string: share url path
     * @return string
     */
    private static function share_folder_opts($foldername, $num=0, $shared=false, $shareurlpath = '') {
        return refworks_base::$renderer->share_folder_opts($foldername, $num, $shared, $shareurlpath);
    }
    public static function share_folder_opts_renderer($foldername, $num=0, $shared=false, $shareurlpath = '') {
        GLOBAl $CFG;
        //$output = '<div class="flditemcontainer_opts">';
        //delete and update (need to be post form so secure as accessing account)
        //$output .= '<form id="ref_foldershare_'.$num.'" method="post" action="sharefolders.php">';
        $foldername = htmlspecialchars($foldername);

        //check delete capability
        $output='<td>';
        // button to share folder and references contained within
        if ($shared == true) {
            $output .= '<a href ="'.self::refshareprefix.$shareurlpath.'">'.get_string('team_shared_folder_link','refworks').'</a>&nbsp;';
            $output .= '<a href ="'.self::refshareprefix.$shareurlpath.self::rsssuffix.'"><img src="'.self::get_image_paths()->rss.'" alt="RSS link to '.$foldername.' folder" title="RSS link to '.$foldername.' folder" /></a></td><td class="ref_cell3"><form id="ref_foldershare_'.$num.'" method="post" action="sharefolders.php"> <input type="submit" value="Remove sharing" alt="Remove sharing of '.$foldername.'" title="Remove sharing of '.$foldername.'" />';
            $output .= '<input type="hidden" name="actiontype" value="remove" />';
        } else {
            $output .= '&nbsp;</td><td class="ref_cell3"><form id="ref_foldershare_'.$num.'" method="post" action="sharefolders.php"> <input type="submit" value="Share folder" name="share_folder" src="'.self::get_image_paths()->delete.'" alt="Share (publish) '.$foldername.'" title="Share (publish) '.$foldername.'" />';
            $output .= '<input type="hidden" name="actiontype" value="share" />';
        }
        $output .= '<div style="display: none;">';
        $output .= '<input type="hidden" name="foldername" value="'.$foldername.'" />';
        $output .= '<input name="sesskey" type="hidden" value="'.sesskey().'" />';
        if (refworks_base::$isinstance) {
            //if in instance also need id to be sent
            $output .= '<input type="hidden" name="id" value="'.refworks_base::$cm->id.'" />';
        }
        $output .= '</div></form></td>';

        //return $output.'</div>';
        return $output;

    }

    private static function create_ref_folderdisplay($refnode) {
        return refworks_base::$renderer->create_ref_folderdisplay($refnode);
    }
    public static function create_ref_folderdisplay_renderer($refnode) {
        //current folder display
        if (refworks_base::check_capabilities('mod/refworks:folders')) {
            $output = '<td class="itemcontainer_item_fld">';
            //show folder info and allow moving items with no folder into a folder
            $fldlist = $refnode->getElementsByTagName('fl');
            if ($fldlist->length != 0) {
                //show what folders this reference is in
                for ($a=0, $max=$fldlist->length; $a<$max; $a++) {
                    $nodeval = $fldlist->item($a)->nodeValue;
                    if ($nodeval!='' && $nodeval!='Last Imported' && $nodeval!='deleted' && $nodeval!='mylist') {
                        $output .= '<p>'.htmlspecialchars(refworks_base::return_foldername($nodeval,true)).'</p>';
                    } else {
                        $output .= '&nbsp;';
                    }
                }
            } else {
                $output .= '&nbsp;';
            }
            $output .= '</td>';
            return $output;
        }
        return '';
    }

    /**
     * Create html of checkbox for reference
     * checkbox has id of r_[ref id]
     * If 'selected' field has matching id in sequence, checkbox will be selected
     * @param $refnode
     * @return string
     */
    private static function create_ref_select($refnode) {
        return refworks_base::$renderer->create_ref_select($refnode);
    }
    public static function create_ref_select_renderer($refnode) {
        $output = '<td class="itemcontainer_sel">';
        if ($id = self::get_element_value($refnode,'id')) {
            $reftitle = ' '.self::get_element_value($refnode,'t1');
            //work out if already selected
            $issel = false;
            //selected vals should always be in selected field
            $selrefs = optional_param('selected','',PARAM_SEQUENCE);
            if ($selrefs!='') {
                $selarray = explode(',',$selrefs);
                if (in_array($id, $selarray)) {
                    $issel = true;
                }
            } else {
                //check if submit select set (i.e. no JS support)
                $issel = optional_param('r_'.$id,false,PARAM_BOOL);
            }
        }
        $output .= print_checkbox('r_'.$id, true, $issel, '', get_string('ref_select','refworks').' '.$reftitle, '' , true);
        $output .= '</td>';
        return $output;
    }

    /**
     * Returns the value of the element in refnode
     * @param $refnode DomNode: xml node to search through
     * @param $elementname string: name of element to find
     * @param $occurance int:Default 1st, otherwise occurance number to look for
     * @return string or FALSE if not found
     */
    private static function get_element_value($refnode, $elementname, $occurance = 1) {
        $nodelist = $refnode->getElementsByTagName($elementname);
        if ($nodelist->length >= $occurance) {
            return $nodelist->item($occurance-1)->nodeValue;
        }
        return false;
    }
}
?>