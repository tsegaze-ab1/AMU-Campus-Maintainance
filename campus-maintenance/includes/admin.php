<?php
// includes/admin.php
// Admin-only helpers.

require_once __DIR__ . '/db.php';

function admin_allowed_roles(): array
{
    // Keep 'staff' for backward compatibility.
    return ['student', 'technician', 'staff', 'admin'];
}

function list_users_basic(): array
{
    global $conn;

    $sql = 'SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC, id DESC';
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmt->bind_result($id, $username, $email, $role, $createdAt);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'username' => (string)$username,
            'email' => (string)$email,
            'role' => (string)$role,
            'created_at' => (string)$createdAt,
        ];
    }

    return $items;
}

function update_user_role(int $userId, string $role): void
{
    global $conn;

    $sql = 'UPDATE users SET role = ? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $role, $userId);
    $stmt->execute();
}

function list_technicians_and_staff(): array
{
    global $conn;

    $sql = "SELECT id, username, email, role FROM users WHERE role IN ('technician','staff') ORDER BY username ASC, email ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmt->bind_result($id, $username, $email, $role);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'username' => (string)$username,
            'email' => (string)$email,
            'role' => (string)$role,
        ];
    }

    return $items;
}
