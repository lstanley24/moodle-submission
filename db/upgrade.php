<?php

defined('MOODLE_INTERNAL') || die;

function xmldb_submission_upgrade($oldversion) {
    global $CFG;

    $result = TRUE;

    if ($oldversion < 2020110907) {

        // Define field cmid to be added to submission_responses.
        $table = new xmldb_table('submission_responses');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'submissiontext');

        // Conditionally launch add field cmid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Submission savepoint reached.
        upgrade_mod_savepoint(true, 2020110907, 'submission');
    }


    return $result;
}
