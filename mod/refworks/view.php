<?php
/**
 * Front screen of module
 *
 * @copyright &copy; 2009 The Open University
 * @author j.platts@open.ac.uk
 * @author Author_Name_2@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package refworks
 */

require_once(dirname(__FILE__).'/refworks_base.php');
require_once(dirname(__FILE__).'/../../local/references/convert/refxml.php');

refworks_base::init();
refworks_base::write_header();
//init rwapi
refworks_connect::require_login();

//ONLY FOR MAIN PAGE - at this point see if we need to populate the user acc with a folder setup in the settings form
if (refworks_connect::$connectok && refworks_base::$isinstance && !isset($SESSION->rwteam) && refworks_base::check_capabilities('mod/refworks:folders')) {
    if (isset(refworks_base::$instance->autopopfolder) && refworks_base::$instance->autopopfolder!='') {
        //check there is data and it is OK (should be as validated in form), if not don't bother to do this
        $dataok = true;
        try{
            require_once(dirname(__FILE__) . '/../../local/references/convert/refxml.php');
            $refxml=new refxml();
            if (!$refxml->test_data_type(stripslashes(refworks_base::$instance->autopopdata),'RefWorksXML')) {
                $dataok = false;
            }
        }catch(Exception $e) {
            $dataok = false;
        }
        if ($dataok) {
            //get correct folder name to check (strip db slashes and add htmlchars)
            $foldname = stripslashes(refworks_base::$instance->autopopfolder);
            global $COURSE;
            if (isset($COURSE->id) && $COURSE->id != SITEID) {
                if (isset($COURSE->shortname)) {
                    $foldname = '[['.$COURSE->shortname.']]'.$foldname;
                }
            }
            //get list of folders in account and check if a match
            $matched=false;
            refworks_folder_api::update_folder_list();
            for ($max=count(refworks_folder_api::$folders)-1;$max>-1;$max--) {
                if (refworks_folder_api::$folders[$max]['name']==$foldname) {
                    $matched = true;
                }
            }
            //if no match then call api to add folder+refs (it will make folder automatically for us)
            if (!$matched) {
                //prepare data
                $data = stripslashes(refworks_base::$instance->autopopdata);
                $refxml = new refxml();
                //convert RWXML to XML
                $references = $refxml->return_transform_in($data,'RefWorksXML');
                //transform to api xml
                $data = $refxml->return_references($references,true);
                unset($refxml);
                //add to folder
                $result = rwapi::add_references($data,'',$foldname);
            }
        }

    }
}

refworks_base::write_sidemenu();

//Main content goes here

GLOBAL $CFG;

// module title - always try and use title from admin settings, if not use lang file
if (!empty(refworks_base::$config->refworks_name)) {
    $strrefworks  = refworks_base::$config->refworks_name;
} else {
    $strrefworks  = get_string('modulename', 'refworks');
}
refworks_base::write_heading($strrefworks);

refworks_connect::write_login_errors();

echo html_writer::start_tag('div', array('class' => 'instructions'));

echo print_string('viewinst', 'refworks', get_docs_url('mod/refworks/view'));

echo html_writer::end_tag('div');

if (refworks_base::$isinstance) {

    //add_to_log(refworks_base::$course->id,'refworks','view','view.php?id='.refworks_base::$cm->id,'Access RefWorks Module',refworks_base::$cm->id);
    $eventdata = array();
    $eventdata['objectid'] = $cm->id;
    $eventdata['context'] = $PAGE->context;
    $event = \mod_refworks\event\course_module_viewed::create($eventdata);
    $event->add_record_snapshot('course', $PAGE->course);
    $event->trigger();
}

refworks_base::write_footer();
?>