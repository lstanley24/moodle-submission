<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information for the matching question type.
 *
 * @package   mod_submission
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/submission/locallib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once("$CFG->libdir/resourcelib.php");

class mod_submission_mod_form extends moodleform_mod {

    //Add elements to form
    public function definition() {

        global $CFG;

        $mform = $this->_form;

        //general section
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // submission instance name
        $mform->addElement('text', 'name', get_string('name', 'submission'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        //editor form element
        $mform->addElement('header', 'contentsection', get_string('content_header', 'submission'));
        $mform->setType('contentsection', PARAM_TEXT);
        $mform->addElement('editor', 'content_editor', get_string('editor_text_top', 'submission'), null, submission_get_editor_options($this->context));
        
        // add all the standard activity form stuff
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    //related to the editor element + formatting. In order for the db to receive content from the editor field, it needs to be reformatted as a string. 
    public function data_preprocessing(&$data) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('content_editor');
            $data['content_editor']['format'] = $data['contentformat'];
            $data['content_editor']['text']   = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_submission',
                    'content', 0, submission_get_editor_options($this->context), $data['content']);
            $data['content_editor']['itemid'] = $draftitemid;
        }
    }

}
