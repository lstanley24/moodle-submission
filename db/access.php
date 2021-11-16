<?php

/**
 * The role capabilities and their defaults.
 */

defined('MOODLE_INTERNAL') || die;

$capabilities = [
    'mod/submission:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'guest' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ],

    // the 'viewresponse' capability is specifically related to user_submissions.php. To ensure that students cannot access the 'view submissions' button or the user_submissions page. 

    'mod/submission:viewresponse' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
         )
    ),

    'mod/submission:datafullaccess' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL | RISK_XSS | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ],

    'mod/submission:addinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL | RISK_XSS | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [] // admins only by default since risks are high
    ]
];
