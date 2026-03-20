<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['student']);

require_once __DIR__ . '/../student_dashboard.php';
