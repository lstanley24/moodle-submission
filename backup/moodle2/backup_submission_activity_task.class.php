<?php

require_once($CFG->dirroot . '/mod/submission/backup/moodle2/backup_submission_stepslib.php'); // Because it exists (must)

/**
 * submission backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_submission_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_submission_activity_structure_step('submission_structure', 'submission.xml'));

    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // encode urls 
        
        $search="/(".$base."\/mod\/submission\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@SUBMISSIONINDEX*$2@$', $content);


        $search="/(".$base."\/mod\/submission\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@SUBMISSIONVIEWBYID*$2@$', $content);


        return $content;
    }
}