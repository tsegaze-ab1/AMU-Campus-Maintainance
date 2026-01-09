<?php
// includes/auth.php
// Beginner-friendly session + authorization helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL handling: localhost uses subfolder, hosting uses site root
if (!defined('BASE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = (stripos($host, 'localhost') !== false) || ($host === '127.0.0.1');
    define('BASE_URL', $isLocal ? '/campus-maintenance' : '');
}

// Optional: allow key-protected admin registration.
// Leave empty ('') to disable admin self-registration.
if (!defined('ADMIN_REGISTRATION_KEY')) {
    define('ADMIN_REGISTRATION_KEY', 'Tsegaab_2026!');
}

function base_url(string $path = ''): string
{
    if ($path === '') {
        return BASE_URL;
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return BASE_URL . $path;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function require_post_with_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }

    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token(is_string($token) ? $token : null)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('/login.php'));
        exit;
    }
}

// Restrict a page to one or more roles.
// Example: require_role(['admin']); or require_role(['student', 'staff']);
function require_role(array $allowedRoles): void
{
    require_login();

    $user = current_user();
    $role = $user['role'] ?? '';

    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function redirect_to_dashboard(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('/login.php'));
        exit;
    }

    $role = ($_SESSION['user']['role'] ?? '');
    switch ($role) {
        case 'admin':
            header('Location: ' . base_url('/admin_dashboard.php'));
            exit;
        case 'staff':
            header('Location: ' . base_url('/staff_dashboard.php'));
            exit;
        case 'technician':
            header('Location: ' . base_url('/technician_dashboard.php'));
            exit;
        case 'student':
            header('Location: ' . base_url('/student_dashboard.php'));
            exit;
        default:
            // Unknown role: force logout for safety.
            logout_user();
            header('Location: ' . base_url('/login.php'));
            exit;
    }
}

function logout_user(): void
{
    // Unset all session variables.
    $_SESSION = [];

    // Delete session cookie.
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
