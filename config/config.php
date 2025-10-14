<?php
// Global configuration for Interview & Testing Portal

return [
    'app_name' => 'Interview & Testing Portal',
    // Set this to your XAMPP URL path, e.g. http://localhost/portal
    'base_url' => 'http://localhost/interview_portal',

    // MySQL credentials (XAMPP defaults: user root, empty password)
    'db_host' => '127.0.0.1',
    'db_name' => 'interview_portal',
    'db_user' => 'root',
    'db_pass' => '',

    // Security
    'session_name' => 'itp_session',
    'debug' => true,
];
