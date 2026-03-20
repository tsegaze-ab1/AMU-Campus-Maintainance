<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';

require_role(['admin']);
require_post_with_csrf();

$action = (string)($_POST['action'] ?? '');
$currentUser = current_user();
$currentUserId = (int)($currentUser['id'] ?? 0);

if ($action === 'add') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = (string)($_POST['role'] ?? 'student');

    if ($username === '' || $password === '') {
        header('Location: ' . base_url('/admin/user_management.php?error=Please+fill+all+required+fields.'));
        exit;
    }

    if (!in_array($role, role_management_allowed_roles(), true)) {
        header('Location: ' . base_url('/admin/user_management.php?error=Invalid+role+selected.'));
        exit;
    }

    if (find_user_by_username($username)) {
        header('Location: ' . base_url('/admin/user_management.php?error=Username+already+exists.'));
        exit;
    }

    if (!create_user_for_role_management($username, $password, $role)) {
        header('Location: ' . base_url('/admin/user_management.php?error=Unable+to+create+user.'));
        exit;
    }

    header('Location: ' . base_url('/admin/user_management.php?success=User+created+successfully.'));
    exit;
}

if ($action === 'delete') {
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$userId) {
        header('Location: ' . base_url('/admin/user_management.php?error=Invalid+user+selected.'));
        exit;
    }

    if ((int)$userId === $currentUserId) {
        header('Location: ' . base_url('/admin/user_management.php?error=You+cannot+delete+your+own+account.'));
        exit;
    }

    if (!delete_user_for_role_management((int)$userId, $currentUserId)) {
        header('Location: ' . base_url('/admin/user_management.php?error=Unable+to+delete+user.'));
        exit;
    }

    header('Location: ' . base_url('/admin/user_management.php?success=User+deleted+successfully.'));
    exit;
}

if ($action === 'edit') {
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $username = trim((string)($_POST['username'] ?? ''));
    $role = (string)($_POST['role'] ?? 'student');
    $newPassword = trim((string)($_POST['new_password'] ?? ''));

    if (!$userId || $username === '') {
        header('Location: ' . base_url('/admin/user_management.php?error=Invalid+data+for+update.'));
        exit;
    }

    if (!in_array($role, role_management_allowed_roles(), true)) {
        header('Location: ' . base_url('/admin/user_management.php?error=Invalid+role+selected.'));
        exit;
    }

    $existing = find_user_by_username($username);
    if ($existing && (int)($existing['id'] ?? 0) !== (int)$userId) {
        header('Location: ' . base_url('/admin/user_management.php?error=Username+already+exists.'));
        exit;
    }

    $passwordForUpdate = $newPassword !== '' ? $newPassword : null;
    if (!update_user_for_role_management((int)$userId, $username, $role, $passwordForUpdate)) {
        header('Location: ' . base_url('/admin/user_management.php?error=Unable+to+update+user.'));
        exit;
    }

    header('Location: ' . base_url('/admin/user_management.php?success=User+updated+successfully.'));
    exit;
}

header('Location: ' . base_url('/admin/user_management.php?error=Invalid+action.'));
exit;
