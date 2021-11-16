<?php

/**
 * Define all the backup steps that will be used by the backup_submission_activity_task
*/

defined('MOODLE_INTERNAL') || die;

class backup_submission_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        //define each element 
        $submission = new backup_nested_element('submission', array('id'), array('name', 'intro', 'introformat', 'content', 'contentformat', 'revision', 'timemodified'));

        // Define sources
        $submission->set_source_table('submission', array('id' => backup::VAR_ACTIVITYID));

        // Define file annotations
        $submission->annotate_files('mod_submission', 'intro', null); 
        $submission->annotate_files('mod_submission', 'content', null); 

        // need child nodes only if userdata is included in backup
          if ($this->get_setting_value('userinfo')) {
            $responses = new backup_nested_element('responses');
            $response = new backup_nested_element('response', ['id'], ['userid', 'response', 'complete']);

            $submission->add_child($responses);
            $responses->add_child($response);

            $response->set_source_table('submission_responses', ['id' => backup::VAR_PARENTID], 'id ASC');

            // map response userid to user table
            $response->annotate_ids('user', 'userid');
        }

        // Return the root element (page), wrapped into standard activity structure
        return $this->prepare_activity_structure($submission);


    }
}