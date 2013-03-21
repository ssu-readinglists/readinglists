<?php //$Id: mod_form.php,v 1.3 2010/03/18 16:04:49 jp5987 Exp $

/**
 * This file defines the main refworks configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_refworks_mod_form extends moodleform_mod {

    private $valrunning = false;

    function definition() {

        global $COURSE;
        $mform =& $this->_form;

        //-------------------------------------------------------------------------------
        /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('refworksname', 'refworks'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'autopopfolder', get_string('refworks_autopopfolder', 'refworks'));
        $mform->addHelpButton('autopopfolder', 'refworks_autopopfolder_info', 'refworks');

        $mform->addElement('filepicker', 'attachment', get_string('refworks_autopopdata', 'refworks'), null, array('maxbytes' => 512000, 'accepted_types' => '.xml'));
        $mform->addElement('textarea', 'autopopdata', get_string('refworks_autopopdatatext', 'refworks'),array('cols'=>55,'rows'=>4));
        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }

    //custom validation to check reference xml entered or uploaded is valid
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        //we must ensure that validation can only run once as get_file_content calls validation = loop
        if (!$this->valrunning) {
            $this->valrunning = true;
            if (!empty($data['autopopfolder'])) {
                if (isset($files['attachment'])) {
                    //file takes precendent
                    $uploaded=$this->get_file_content('attachment');
                    require_once(dirname(__FILE__) . '/../../local/references/convert/refxml.php');
                    $refxml = new refxml();
                    try{
                        if (!$refxml->test_data_type(stripslashes($uploaded),'RefWorksXML')) {
                            $errors['attachment']= get_string('refworks_autopopdataerror','refworks');
                        }
                    }catch(Exception $e) {
                        $errors['attachment']= get_string('refworks_autopopdataerror','refworks');
                    }
                }else if ($data['autopopdata']!='') {
                    try{
                        require_once(dirname(__FILE__) . '/../../local/references/convert/refxml.php');
                        $refxml = new refxml();
                        if (!$refxml->test_data_type(stripslashes($data['autopopdata']),'RefWorksXML')) {
                            $errors['autopopdata']= get_string('refworks_autopopdataerror','refworks');
                        }
                    }catch(Exception $e) {
                        $errors['autopopdata']= get_string('refworks_autopopdataerror','refworks');
                    }
                }
            }
            $this->valrunning = false;
        }
        return $errors;
    }
}

?>
