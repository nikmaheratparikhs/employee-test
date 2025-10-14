<?php
require_once __DIR__ . '/includes/auth.php';
logout_user();
header('Location: ' . base_url('login.php'));
exit;
