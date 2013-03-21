<?php
/**
 * Base (static) class for the refworks module.
 * Should be included on all pages
 * Contains methods for general page use - e.g. initialisation, headers etc
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
include_once(dirname(__FILE__).'/lib.php');
include_once(dirname(__FILE__).'/refworks_connect.php');
require_once(dirname(__FILE__).'/refworks_folder_api.php');
require_once(dirname(__FILE__).'/../../local/references/rwapi/rwapi.php');
include_once(dirname(__FILE__).'/collab/refworks_collab_lib.php');

class refworks_base {
    public static $course = null;
    public static $cm = null;
    public static $instance = null;

    public static $isinstance = false;

    public static $baseloc = 'mod/refworks';

    private static $hasleftmenu = false;

    public static $config;

    public static $renderer;

    /**
     * Initialise the page, setting up moodle vars, checking login
     * Also sets up whether access has come from instance or direct
     * THIS MUST BE CALLED BY ALL PAGES IN THE MODULE
     * @return
     */
    public static function init() {
        global $DB, $CFG, $PAGE;

        $id = optional_param('id', 0, PARAM_INT); // course_module ID, or
        $a  = optional_param('a', 0, PARAM_INT);  // refworks instance ID

        if ($id) {
            if (! $cm = get_coursemodule_from_id('refworks', $id)) {
                error('Course Module ID was incorrect');
            }

            if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
                error('Course is misconfigured');
            }

            if (! $refworks = $DB->get_record('refworks', array('id' => $cm->instance))) {
                error('Course module is incorrect');
            }
            self::$isinstance = true;
            self::$cm = $cm;
            self::$course = $course;
            self::$instance = $refworks;
        } else if ($a) {
            if (! $refworks = $DB->get_record('refworks', array('id' => $a))) {
                error('Course module is incorrect');
            }
            if (! $course = $DB->get_record('course', array('id' => $refworks->course))) {
                error('Course is misconfigured');
            }
            if (! $cm = get_coursemodule_from_instance('refworks', $refworks->id, $course->id)) {
                error('Course Module ID was incorrect');
            }
            self::$isinstance = true;
            self::$cm = $cm;
            self::$course = $course;
            self::$instance = $refworks;
        } else {
            //direct access
        }
        //require logged in to moodle
        if (self::$isinstance) {
            require_login(self::$course, false, self::$cm);
        } else {
            require_login(0, false);
            $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
        }

        //set the base folder location of this mod (used in links/css etc so we can have sub-folders)
        self::$baseloc = $CFG->wwwroot.'/'.self::$baseloc.'/';

        //Get module config settings
        self::$config = get_config('mod_refworks');
    }

    /**
     * Prints header, changes breadcrumb depending on whether instance or standalone
     * @param $breadcrumbs array: array of moodle breadcrumb array (optional)
     * @return
     */
    public static function write_header($breadcrumbs=array(), $curfile = '') {
        GLOBAL $CFG, $OUTPUT, $PAGE;
		$PAGE->set_pagelayout('refworks');
        // Breadcrumb module title - always try and use title from admin settings, if not use lang file
        if (!empty(self::$config->refworks_name)) {
            $strrefworks  = self::$config->refworks_name;
        } else {
            $strrefworks  = get_string('modulename', 'refworks');
        }

        if ($curfile == '') {
            //check link against the current php file
            //get file name
            $curfile = substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1);
        }

        $params = null;
        if (self::$isinstance) {
            $params = array('id' => self::$cm->id);
        }

        //HEAD code (css, js meta tags etc)
        $PAGE->set_url('/mod/refworks/'.$curfile, $params);


        if (!self::$isinstance) {
            $PAGE->navbar->add($strrefworks, new moodle_url('/mod/refworks/index.php'));
        }

        if (count($breadcrumbs>0)) {
            for ($a=0,$max=count($breadcrumbs);$a<$max;$a++) {
                if (!empty($breadcrumbs[$a]['link'])) {
                    $PAGE->navbar->add($breadcrumbs[$a]['name'], new moodle_url($breadcrumbs[$a]['link']));
                } else {
                    $PAGE->navbar->add($breadcrumbs[$a]['name']);
                }
            }
        }

        if (self::$isinstance) {
            $PAGE->set_title(htmlspecialchars(format_string(self::$instance->name)));
            $PAGE->set_heading(self::$course->fullname);
            $PAGE->set_button(update_module_button(self::$cm->id, self::$course->id, $strrefworks));
        } else {
            $PAGE->set_title(htmlspecialchars($strrefworks));
        }

        //$PAGE->requires->css('/mod/refworks/refworks.css');
        $PAGE->requires->js('/mod/refworks/refworks.js');

        $jsmodule = array(
            'name'  =>  'mod_refworks',
            'fullpath'  =>  '/mod/refworks/module.js',
            'requires'  =>  array('base', 'node', 'autocomplete-base', 'autocomplete-filters')
        );
        $jsdata = array();
        $PAGE->requires->js_init_call('M.mod_refworks.init',
                                            $jsdata,
                                            false,
                                            $jsmodule);
		

        self::$renderer = $PAGE->get_renderer('mod_refworks');

        echo $OUTPUT->header();

        //wrapper div for all module content
        print('<div id="refcontent">');
    }

    /**
     * Creates footer code
     * @return
     */
    public static function write_footer() {
        global $PAGE, $OUTPUT;
        if (self::$hasleftmenu) {
            //write end tag for central div
            echo('</div>');
        }
        echo('</div>');//refcontent div close
        //clear
        echo('<div class="clearer"></div>');


        echo $OUTPUT->footer();
    }

    /**
     * prints out the side menu, you must call this after header and before writing any content
     * @param $curfile string:file name of current file (matched to items to highlight current)
     * @return
     */
    public static function write_sidemenu($curfile='', $cursharedacct='') {
        self::$renderer->write_sidemenu($curfile, $cursharedacct);
    }

    /**
     * DO NOT CALL DIRECTLY - USED BY RENDERER
     * @param $curfile string:file name of current file (matched to items to highlight current)
     * @return
     */
    public static function write_sidemenu_renderer($curfile='', $cursharedacct='') {

        self::$hasleftmenu = true;
        echo('<div id="left-column">');
        global $CFG, $PAGE;

        $refworkslink = isset(self::$config->refworks_link)? self::$config->refworks_link : '';

        //div for module side menu (needs own css rules)
        echo('<div id="sidemenu">');
        /*menu array and sub arrays defining menu structure
         * menuararray - each element (array) represents a side block
         * menu block array, assoc array - title:heading , reqcap:comma sep list of capabilities needed to show block, items:array of menu items
         * item assoc array - title:link text, link:url, reqcap:comma sep list of capabilities needed to show item, style:string class of item
         */
        $menuarray = array(
        array(
            'title' => get_string('references','refworks'),
            'reqcap' => 'mod/refworks:connect',
            'items' => array(
        array('title' => get_string('view_all_refs','refworks'), 'link' => 'viewrefs.php'),
        array('title' => get_string('create_ref','refworks'), 'link' => 'createref.php', 'reqcap' => 'mod/refworks:update'),
        array('title' => get_string('import_ref','refworks'), 'link' => 'importrefs.php'),
        array('title' => get_string('export_ref','refworks'), 'link' => 'exportrefs.php', 'reqcap' => 'mod/refworks:export'),
        array('title' => get_string('create_bib','refworks'), 'link' => 'createbib.php', 'reqcap' => 'mod/refworks:bibliography'),
        array('title' => get_string('reports','refworks'), 'link' => 'reports/report.php', 'reqcap' => 'mod/refworks:runreport')
        )
        ),
        array(
            'title' => get_string('folders','refworks'),
            'reqcap' => 'mod/refworks:folders',
            'items' => array(
        array('title' => get_string('create_folder','refworks'), 'link' => 'createfolder.php'),
        array('title' => get_string('manage_folder','refworks'), 'link' => 'managefolders.php')
        )
        ),
        //Collaborative
        array(
            'title' => get_string('team_accounts','refworks'),
            'reqcap' => 'mod/refworks:collaboration',
            'items' => array(
        array('title' => get_string('team_createacc','refworks'), 'link' => 'collab/collab_create.php', 'reqcap'=>'mod/refworks:collaboration_createacc'),
        array('title' => get_string('team_manageacc','refworks'), 'link' => 'collab/collab_manage.php', 'reqcap'=>'mod/refworks:collaboration_createacc'),
        )
        ),
        array(
            'title' => get_string('refworks','refworks'),
            'reqcap' => 'refworks',
            'items' => array(
        array('title' => get_string('access_account','refworks'), 'link' => $refworkslink),
        )
        ),
		// Modified to link to RefWorks help page, rather than (non-existent) Moodle documentation on mod. 27/03/2012. owen@ostephens.com
        array(
                'title' => get_string('support','refworks'),
                'items' => array(
                    array('title' => get_string('access_help','refworks'), 'link' => 'https://www.refworks.com/RWSingle/help/helpmainframe.asp'),
                    )
                )
        );

        //Add in an extra support link
        if (get_string('access_libnews_link', 'refworks') != '') {
            array_push($menuarray[4]['items'], array('title' => get_string('access_libnews','refworks'), 'link' => get_string('access_libnews_link', 'refworks'))
            );
        }

        //Add user folders to folder block
        refworks_folder_api::update_folder_list();
        for ($max=count(refworks_folder_api::$folders)-1;$max>-1;$max--) {
            array_unshift($menuarray[1]['items'], array('title' => self::return_foldername(refworks_folder_api::$folders[$max]['name'],true), 'link' => 'viewfolder.php?folder='.urlencode(refworks_folder_api::$folders[$max]['name']), 'style' => 'folder'));
        }

        if (self::check_capabilities('mod/refworks:collaboration')) {
            global $USER, $SESSION;
            //Collaboration - Check if user can access any accounts + add to menu
            if (count(refworks_collab_lib::get_user_accounts($USER->id))>0) {
                array_push($menuarray[2]['items'], array('title' => get_string('team_login','refworks')));
				array_push($menuarray[2]['items'], array('title' => 'Filter:', 'input' => 'ac-input', 'type' => 'text'));
                foreach (refworks_collab_lib::$accsavail as $acc) {
                    array_push($menuarray[2]['items'], array('title' => stripslashes($acc->name), 'link'=>'collab/collab_login.php?accid='.$acc->id, 'style'=>'account'));
                }
            }
            //If user is logged in to an account - present them with a logout option
            if (isset($SESSION->rwteam)) {
                array_push($menuarray[2]['items'], array('title' => get_string('team_logout','refworks'), 'link'=>'collab/collab_logout.php'));
                //In a shared account, add publish link to folder menu
                array_push($menuarray[1]['items'], array('title' => get_string('share_folder','refworks'), 'link' => 'sharefolders.php'));
                $cursharedacct = 'collab/collab_login.php?accid='.$SESSION->rwteam;
            }
        }

        //create menu html
        for ($a=0, $max=count($menuarray); $a<$max; $a++) {
            //check block capabilities so can display
            if (isset($menuarray[$a]['reqcap'])) {
                //special case: refworks - check logged in to an individual account
                if ($menuarray[$a]['reqcap'] == 'refworks') {
                    if (!refworks_connect::$connectok || isset($SESSION->rwteam)) {
                        continue;
                    }
                }else if (!self::check_capabilities($menuarray[$a]['reqcap'])) {
                    //user does not have capability so do not show
                    continue;
                }
            }
            $blockcontent = '';
            if (isset($menuarray[$a]['items'])) {
                $blockcontent = '<ul class="section">';
                for ($b=0, $max2=count($menuarray[$a]['items']); $b<$max2; $b++) {
                    //check capabilities so can display item
                    if (isset($menuarray[$a]['items'][$b]['reqcap'])) {
                        if (!self::check_capabilities($menuarray[$a]['items'][$b]['reqcap'])) {
                            //user does not have capability so do not show
                            continue;
                        }
                    }

                    if (isset($menuarray[$a]['items'][$b]['style'])) {
                        //item has a class
                        $blockcontent .= '<li class="'.$menuarray[$a]['items'][$b]['style'].'">';
                    } else {
                        $blockcontent .= '<li>';
                    }
                    //link or not?
                    if (isset($menuarray[$a]['items'][$b]['link']) && $menuarray[$a]['items'][$b]['link']!='') {
                        //check if current option (selected)
                        $linkselected = false;

                        if ($curfile == '') {
                            //check link against the current php file
                            //get file name
                            $curfile = substr($_SERVER['PHP_SELF'],strrpos($_SERVER['PHP_SELF'],'/')+1);
                        }

                        if (($menuarray[$a]['items'][$b]['link'] == $curfile) || ($cursharedacct!=='' && $menuarray[$a]['items'][$b]['link'] == $cursharedacct)) {
                            $linkselected = true;
                        }

                        $blockcontent .= '<a href="'.self::return_link($menuarray[$a]['items'][$b]['link']).'"><span';
                        if ($linkselected) {
                            $blockcontent .= ' class="current"';
                        }
                        $blockcontent .= '>'.htmlspecialchars($menuarray[$a]['items'][$b]['title']).'</span></a>';
					// Is it an input? If so set type and use title as label
					} else if (isset($menuarray[$a]['items'][$b]['input']) && $menuarray[$a]['items'][$b]['input']!='') {
						$blockcontent .= '<label for="'.$menuarray[$a]['items'][$b]['input'].'">'.htmlspecialchars($menuarray[$a]['items'][$b]['title']).'</label>';
						$blockcontent .= '<input id="'.$menuarray[$a]['items'][$b]['input'].'" type="'.$menuarray[$a]['items'][$b]['type'].'">';
                    } else {
                        $blockcontent .= '<span>'.htmlspecialchars($menuarray[$a]['items'][$b]['title']).'</span>';
                    }
                    $blockcontent .= '</li>';
                }
                $blockcontent .= '</ul>';
            }
            //print_side_block($menuarray[$a]['title'],$blockcontent,NULL,NULL,'',NULL,$menuarray[$a]['title']);
            global $OUTPUT;
            $block = new block_contents();
            $block->content = $blockcontent;
            $block->title = $menuarray[$a]['title'];
            echo $OUTPUT->block($block, '');
        }



        echo('</div><div class="refworkslogo"><img src="'.self::$baseloc.'pix/refworks.png" alt="Powered by RefWorks"/></div></div>');//end of sidemenu, end of column
        //setup main content column
        echo('<div id="middle-column" class="has-left-column">');
    }

    /**
     * Prints main page title
     * @param $text string
     * @return
     */
    public static function write_heading($text) {
        global $OUTPUT;
        echo $OUTPUT->heading($text, 1, 'main-title');
    }

    public static function write_error($text) {
        global $OUTPUT;
        echo $OUTPUT->error_text($text);
        //echo('<div class="box errorbox errorboxcontent">'.$text.'</div>');
    }

    /**
     * Wrapper for moodle has_capability
     * Use this method instead as works in standalone + instance contexts
     * @param $capstr string: capabilities to check (, separated)
     * @return bool: true if user passes all
     */
    public static function check_capabilities($capstr, $userid=null) {
        global $CFG;
        $caparr = explode(',',$capstr);
        $capok = true;

        //include capabilities array so we can check context
        require(dirname(__FILE__).'/db/access.php');

        foreach ($caparr as $cap) {
            //get context level
            $contextlevel = $capabilities[$cap]['contextlevel'];

            //Gets the current context, based on required level
            if ($contextlevel == CONTEXT_MODULE && self::$isinstance) {
                $context=get_context_instance(CONTEXT_MODULE, self::$cm->id);
            }else if ($contextlevel == CONTEXT_COURSE && self::$isinstance) {
                $context=get_context_instance(CONTEXT_COURSE, self::$course->id);
            }else if ($contextlevel == CONTEXT_SYSTEM) {
                $context=get_context_instance(CONTEXT_SYSTEM);
            } else {
                break;
            }

            if ($userid==0) {
                if (!has_capability($cap, $context)) {
                    $capok = false;
                }
            } else {
                if (!has_capability($cap, $context, $userid)) {
                    $capok = false;
                }
            }
        }

        return $capok;
    }

    /**
     * Takes a link and appends instance params (id=) if needed
     * @param $link string:url
     * @return string
     */
    public static function return_link($link) {
        //If running as instance and is a php not an external link or another moodle file
        if (strpos($link,'.php')) {
            //check not external or another moodle page
            if (!strpos($link,'://') && !strpos($link,'../')) {
                if (self::$isinstance) {
                    if (strpos($link,'?')===false) {
                        $link .= '?id='.self::$cm->id;
                    }else if (strpos($link,'&amp;id')===false) {
                        $link .= '&amp;id='.self::$cm->id;
                    }
                }
                return self::$baseloc.$link;
            }
        }
        //external link return as is
        return $link;
    }

    /**
     * Takes a folder name got from optional_param etc and cleans it up to return the exact name
     * so, my+folder becomes my folder
     * @param $fld string: folder name
     * @param $display bool: optional - true if the name is for display to user only
     * @return string : correct folder name
     */
    public static function return_foldername($fld, $display=false) {
        $fld = rawurldecode($fld);
        $fld = stripslashes($fld);
        if ($display && strpos($fld,'[[')===0 && strpos($fld,']]')) {
            //remove [[]] prefix for auto-populated folders
            $fld = substr($fld,strpos($fld,']]')+2);
        }
        if ($display) {
            //replace special chars that have replaced []
            $fld = str_replace('``', '[', $fld);
            $fld = str_replace('¬¬', ']', $fld);
        }
        return $fld;
    }
}
?>