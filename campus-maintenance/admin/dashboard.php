<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['admin']);

require_once __DIR__ . '/../admin_dashboard.php';
