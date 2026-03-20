<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

// Public entrypoint uses the designed frontend template.
header('Location: ' . base_url('/frontend-php-ready/index.html'));
exit;
