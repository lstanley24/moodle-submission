<?php

/**
 * Structure step to restore one submission activity
 */

class restore_submission_activity_structure_step extends restore_activity_structure_step {

    //define restore tree struction: submission >>> submission_responses
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $paths = [new restore_path_element('submission', '/activity/submission')];
       
        if ($userinfo) {
            $paths[] = new restore_path_element('submission_response', '/activity/submission/responses/response');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    //restore submission table entry

    protected function process_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        //insert the submission record
        $newitemid = $DB->insert_record('submission', $data);

        //immediately after inserting submission record, call this
        $this->apply_activity_instance($newitemid);
    }

    //restore submission_responses table entry

    protected function process_submission_response($data) {
        global $DB;

        $data = (object)$data;

        $data->devpageid = $this->get_new_parentid('devpage');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('devpage_responses', $data);
    }


    protected function after_execute() {
        // Add submission related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_submission', 'intro', null);
        $this->add_related_files('mod_submission', 'content', null);
    }


}
   