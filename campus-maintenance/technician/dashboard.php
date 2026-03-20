<?php
require_once __DIR__ . '/../includes/auth.php';

require_role(['technician']);

require_once __DIR__ . '/../technician_dashboard.php';
