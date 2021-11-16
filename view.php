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
 * @copyright 2021, Laura
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The page that is shown when viewing a submission instance.
*/

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/mod/submission/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

global $DB;


//cmid = course module id, btw
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$cmid = optional_param('id', 0, PARAM_INT);
$p       = optional_param('p', 0, PARAM_INT);  // Page instance ID
$cm = get_coursemodule_from_id('submission', $cmid);
if (!$cm) {
    print_error('invalidcoursemodule');
}

$submission = $DB->get_record('submission', array('id'=>$cm->instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// make sure user is logged in and capable of viewing
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/submission:view', $context);


$PAGE->set_url('/mod/submission/view.php', array('id' => $cm->id));
$PAGE->set_title('submission');
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($submission);

// This is formatted like this rather than just this $content = $submission->content; so that uploaded files are displayed properly. This section was taken from mod_page
$content = file_rewrite_pluginfile_urls($submission->content, 'pluginfile.php', $context->id, 'mod_submission', 'content', $submission->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;
$content = format_text($content, $submission->contentformat, $formatoptions);

// this is the front end facing submission box

class submissionbox extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form; 
        
        $mform->addElement('textarea', 'submissiontext', get_string('submission_text', 'submission'),  'wrap="virtual" rows="5" cols="45"');
        $mform->setType('submissiontext', PARAM_NOTAGS);  //Set type of element
       
        $this->add_action_buttons();
    }
}

$mform = new submissionbox($CFG->wwwroot . '/mod/submission/view.php?id='. $cm->id);

// //Form processing and displaying is done here
if ($mform->is_cancelled()) {

   redirect($CFG->wwwroot . ('/mod/submission/view.php?id='. $cm->id),  get_string('cancelled_form', 'submission'));

} else if ($fromform = $mform->get_data()) {
    //Insert the data into our database table
    $recordtoinsert = new stdClass();
    $recordtoinsert->submissiontext = $fromform->submissiontext;
    $recordtoinsert->course = $cm->course;
    $recordtoinsert->cmid = $cm->id;
    
    $DB->insert_record('submission_responses', $recordtoinsert);

    redirect($CFG->wwwroot . ('/mod/submission/view.php?id='. $cm->id),  get_string('created_form', 'submission'));
} 

// Outputting everything on the page

echo $OUTPUT->header();
echo $OUTPUT->box($content, "generalbox center clearfix");

//displays the form
$mform->display();

// If user is !not a student, show view submission button
$canviewbutton = has_capability('mod/submission:viewresponse', $context);

if ($canviewbutton === true) {
    $data = [
        'editurl' => new moodle_url('/mod/submission/user_submissions.php',  array('id' => $cm->id)),
    ];
    
    echo $OUTPUT->render_from_template('mod_submission/response_button', $data);
} 

echo $OUTPUT->footer();
