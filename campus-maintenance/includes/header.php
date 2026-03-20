<?php
// includes/header.php

require_once __DIR__ . '/layout.php';

$title = $title ?? ($pageTitle ?? 'Campus Maintenance');
render_header((string)$title);
