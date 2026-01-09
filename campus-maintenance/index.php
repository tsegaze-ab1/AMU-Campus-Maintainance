<?php
require_once __DIR__ . '/includes/auth.php';

if (!is_logged_in()) {
    header('Location: ' . base_url('/login.php'));
    exit;
}

redirect_to_dashboard();
