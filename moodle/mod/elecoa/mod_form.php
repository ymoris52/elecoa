<?php

/**
 * The main elecoa configuration form
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

require_once(dirname(__FILE__) . '/locallib.php');

class mod_elecoa_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $COURSE;
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
        /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        /// Adding the standard "name" field
        $mform->addElement('text', 'name', elecoa_get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

//-------------------------------------------------------------------------------
        /// Adding the rest of elecoa settings, spreeading all them into this fieldset
        /// or adding more fieldsets ('header' elements) if needed for better logic
        // New local package upload
        $maxbytes = get_max_upload_file_size($CFG->maxbytes, $COURSE->maxbytes);
        $mform->setMaxFileSize($maxbytes);
        $mform->addElement('filepicker', 'packagefile', elecoa_get_string('package'));
        $mform->addRule('packagefile', null, 'required', null, 'client');
        $mform->addElement('static', 'packagefilewarning', "&nbsp;", elecoa_get_string('packagefilewarning'));

        //-------------------------------------------------------------------------------
        // Time restrictions
        $mform->addElement('header', 'timerestricthdr', elecoa_get_string('timerestrict'));

        $mform->addElement('date_time_selector', 'timeopen', elecoa_get_string("packageopen"), array('optional' => TRUE));
        $mform->addElement('date_time_selector', 'timeclose', elecoa_get_string("packageclose"), array('optional' => TRUE));

//-------------------------------------------------------------------------------
        // Other Settings
        $mform->addElement('header', 'advanced', get_string('othersettings', 'form'));

//-------------------------------------------------------------------------------
        // What Grade
        $mform->addElement('select', 'whatgrade', elecoa_get_string('whatgrade'),  elecoa_get_what_grade_array());
        $mform->addHelpButton('whatgrade', 'whatgrade', ELECOA_MODULE);
        $mform->setDefault('whatgrade', ELECOA_HIGHESTATTEMPT);

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }

    function data_preprocessing(&$default_values) {
        $draftitemid = file_get_submitted_draft_itemid('packagefile');
        file_prepare_draft_area($draftitemid, $this->context->id, MOD_ELECOA_MODULE, 'package', 0);
        $default_values['packagefile'] = $draftitemid;

        if (isset($default_values['instance'])) {
            $default_values['datadir'] = $default_values['instance'];
        }
    }
    
    function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);
        
        if (empty($data['packagefile'])) {
            $errors['packagefile'] = get_string('required');
        }
        else {
            $files = $this->get_draft_files('packagefile');
            if (count($files) < 1) {
                $errors['packagefile'] = get_string('required');
                return $errors;
            }
            $file = reset($files);
            $filename = $CFG->tempdir.'/temp/elecoaimport/elecoa_'.time();
            make_temp_directory('temp/elecoaimport');
            $file->copy_content_to($filename);
            
            $packer = get_file_packer('application/zip');
            
            $filelist = $packer->list_files($filename);
            if (!is_array($filelist)) {
                $errors['packagefile'] = elecoa_get_string('incorrectpackage');
            }
            else {
                $manifestpresent = FALSE;
                foreach ($filelist as $info) {
                    if ($info->pathname == 'elecoa.xml' or $info->pathname == 'imsmanifest.xml' or $info->pathname == 'cmi5.xml') {
                        $manifestpresent = TRUE;
                        break;
                    }
                }
                if (!$manifestpresent) {
                    $errors['packagefile'] = elecoa_get_string('incorrectpackage');
                }
            }
            unlink($filename);
        }

        return $errors;
    }

    function set_data($default_values) {
        $default_values = (array)$default_values;

        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }
}
