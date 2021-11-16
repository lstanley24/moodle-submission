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

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

//does the user have access to this page?
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/submission:viewresponse', $context);

$PAGE->set_url('/mod/submission/user_submissions.php', array('id' => $cm->id));
$PAGE->set_title(get_string('user_submissions', 'submission'));
$PAGE->set_heading($course->fullname);

$responses =  $DB->get_records_list('submission_responses', 'cmid', array('cmid' => $cm->id));

echo $OUTPUT->header();

$templatecontext = (object)[
    'responses' => array_values($responses),
    'returnurl' => new moodle_url("$CFG->wwwroot/course/view.php?id=$course->id"),
];

echo $OUTPUT->render_from_template('mod_submission/responses', $templatecontext);

echo $OUTPUT->footer();
