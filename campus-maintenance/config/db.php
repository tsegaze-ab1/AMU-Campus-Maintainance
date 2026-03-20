<?php
// config/db.php
// Single source of truth for MySQL connection.
// Works for both localhost (XAMPP) and shared hosting (including InfinityFree).

// Helper: read environment variables, with fallback.
function cm_env($key, $default = '')
{
  $value = getenv($key);
  if ($value === false || $value === null) {
    return $default;
  }
  $value = (string)$value;
  return $value === '' ? $default : $value;
}

// Optional helper file for production hosts that do not support env vars.
// Create config/db.host.php (DO NOT commit) and return:
// <?php return ['host'=>'...', 'user'=>'...', 'pass'=>'...', 'name'=>'...'];
function cm_load_host_db_config()
{
  $file = __DIR__ . '/db.host.php';
  if (!is_file($file)) {
    return [
      'host' => '',
      'user' => '',
      'pass' => '',
      'name' => '',
    ];
  }

  $cfg = require $file;
  if (!is_array($cfg)) {
    return [
      'host' => '',
      'user' => '',
      'pass' => '',
      'name' => '',
    ];
  }

  return [
    'host' => (string)($cfg['host'] ?? ''),
    'user' => (string)($cfg['user'] ?? ''),
    'pass' => (string)($cfg['pass'] ?? ''),
    'name' => (string)($cfg['name'] ?? ''),
  ];
}

// Detect local dev.
$httpHost = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
$isLocal = ($httpHost === '')
  || (stripos($httpHost, 'localhost') !== false)
  || (stripos($httpHost, '127.0.0.1') !== false)
  || (PHP_SAPI === 'cli');

if ($isLocal) {
  // XAMPP defaults (override with CM_DB_* env vars).
  $host = cm_env('CM_DB_HOST', '127.0.0.1');
  $username = cm_env('CM_DB_USER', 'root');
  $password = cm_env('CM_DB_PASS', '');
  $dbname = cm_env('CM_DB_NAME', 'campus_maintenance');
} else {
  // Shared hosting: prefer env vars; fallback to config/db.host.php.
  $hostFile = cm_load_host_db_config();
  $host = cm_env('CM_DB_HOST', $hostFile['host']);
  $username = cm_env('CM_DB_USER', $hostFile['user']);
  $password = cm_env('CM_DB_PASS', $hostFile['pass']);
  $dbname = cm_env('CM_DB_NAME', $hostFile['name']);
}

$missingConfig = ($host === '') || ($username === '') || ($dbname === '');
if ($missingConfig) {
  $help = $isLocal
    ? 'Set local DB in config/db.php or CM_DB_* env vars.'
    : 'Set hosting DB credentials using CM_DB_* env vars or config/db.host.php.';

  error_log('Database configuration is incomplete. ' . $help);
  exit('Database configuration is incomplete. ' . $help);
}

// Prevent uncaught mysqli exceptions on hosts using strict mysqli reporting.
if (function_exists('mysqli_report')) {
  mysqli_report(MYSQLI_REPORT_OFF);
}

$conn = false;
try {
  $conn = mysqli_connect($host, $username, $password, $dbname);
} catch (Throwable $e) {
  error_log('Database connection exception: ' . $e->getMessage());
  $conn = false;
}

if (!$conn) {
  $connectError = function_exists('mysqli_connect_error') ? mysqli_connect_error() : '';
  $msg = 'Database connection failed';
  if ($connectError !== '') {
    $msg .= ': ' . $connectError;
  }

  if ($isLocal) {
    $msg .= "\n\nLocal setup:\n- Start Apache + MySQL in XAMPP\n- Create DB named '" . $dbname . "' in phpMyAdmin\n- Import schema.sql";
  } else {
    $msg .= "\n\nHosting setup:\n- Use the exact MySQL hostname from your hosting control panel\n- Verify DB name, DB user, and DB password\n- Ensure the DB user is assigned to that database";
  }

  error_log($msg);
  exit($msg);
}

mysqli_set_charset($conn, 'utf8mb4');

