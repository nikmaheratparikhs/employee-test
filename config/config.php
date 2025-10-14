<?php
// Global configuration for Interview & Testing Portal

return [
    'app_name' => 'Interview & Testing Portal',
    // Leave empty to auto-detect (recommended). Or set e.g. http://localhost/interview_portal
    'base_url' => '',

    // MySQL credentials (XAMPP defaults: user root, empty password)
    'db_host' => '127.0.0.1',
    'db_name' => 'interview_portal',
    'db_user' => 'root',
    'db_pass' => '',

    // Security
    'session_name' => 'itp_session',
    'debug' => true,
];
