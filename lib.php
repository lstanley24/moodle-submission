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
 * @package mod_submission
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in Submission module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if module supports feature, false if not, null if doesn't know
 */
function submission_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        default: return null;
    }
}


/* ------------ Instance CRUD ------------ */
/**
 * Add submission instance.
 * @param stdClass $data
 * @param submission_mod_form $mform
 * @return int new submission instance id
 */
function submission_add_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;
    $data->timemodified = time();

    if ($mform) {
        $data->content       = $data->content_editor['text'];
        $data->contentformat = $data->content_editor['format'];
    }

    $data->id = $DB->insert_record('submission', $data);
 
    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    $context = context_module::instance($cmid);

    if ($mform and !empty($data->content_editor['itemid'])) {
        $draftitemid = $data->content_editor['itemid'];
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_submission', 'content', 0, submission_get_editor_options($context), $data->content);
        $DB->update_record('submission', $data);
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'submission', $data->id, $completiontimeexpected);

    return $data->id;
}


/**
 * Update submission instance.
 * @param stdClass $data
 * @param submission_mod_form $mform
 * @return bool true
 */
function submission_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;
    $draftitemid = $data->content_editor['itemid'];

    $data->timemodified = time();
    $data->id = $data->instance;

    $data->content       = $data->content_editor['text'];
    $data->contentformat = $data->content_editor['format'];

    $DB->update_record('submission', $data);

    //submission_get_editor_options function lives in locallib.php

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_submission', 'content', 0, submission_get_editor_options($context), $data->content);
        $DB->update_record('submission', $data);
    }

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'submission', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Delete submission instance
 * @param int $id
 * @return bool true
 */
function submission_delete_instance($id) {
    global $DB;

    if (!$DB->delete_records("submission_responses", ['id'=>$id])) {
        return false;
    }
    if (!$DB->delete_records("submission", ['id'=>$id])) {
        return false;
    }

    return true;
}


function submission_view($submission, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $submission->id
    );

    $event = \mod_submission\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('submission', $submission);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}



// all of the below info is so that any photos + uploads in the editor form field display properly. Taken from lib.php in mod_page

function submission_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/submission:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    // $arg could be revision number or index.html
    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // serve page content
        $filename = $arg;

        if (!$page = $DB->get_record('page', array('id'=>$cm->instance), '*', MUST_EXIST)) {
            return false;
        }

        // We need to rewrite the pluginfile URLs so the media filters can work.
        $content = file_rewrite_pluginfile_urls($page->content, 'webservice/pluginfile.php', $context->id, 'mod_submission', 'content', $page->revision);
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $content = format_text($content, $page->contentformat, $formatoptions);

        // Remove @@PLUGINFILE@@/.
        $options = array('reverse' => true);
        $content = file_rewrite_pluginfile_urls($content, 'webservice/pluginfile.php', $context->id, 'mod_submission', 'content',
                                                $submission->revision, $options);
        $content = str_replace('@@PLUGINFILE@@/', '', $content);

        send_file($content, $filename, 0, 0, true, true);
    } else {
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_submission/$filearea/0/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            $page = $DB->get_record('submission', array('id'=>$cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($page->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_submission', 'content', 0)) {
                return false;
            }
            //file migrate - update flag
            $page->legacyfileslast = time();
            $DB->update_record('submission', $page);
        }

        // finally send the file
        send_stored_file($file, null, 0, $forcedownload, $options);
    }
}
