<?php
// includes/user.php
// Database helpers related to user accounts.
// All queries use MySQLi prepared statements.

require_once __DIR__ . '/db.php';

/**
 * Find a user row by email.
 *
 * @return array|null Returns associative array if found, otherwise null.
 */
function find_user_by_email(string $email): ?array
{
    global $conn;

    $sql = 'SELECT id, username, email, password_hash, role FROM users WHERE email = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();

    // Use bind_result instead of get_result() for broader compatibility.
    $stmt->bind_result($id, $username, $emailOut, $passwordHash, $role);
    if (!$stmt->fetch()) {
        return null;
    }

    return [
        'id' => (int)$id,
        'username' => (string)$username,
        'email' => (string)$emailOut,
        'password_hash' => (string)$passwordHash,
        'role' => (string)$role,
    ];
}

/**
 * Create a new user.
 *
 * Note: role is intentionally defaulted to 'student' to avoid privilege escalation.
 * Admin/staff accounts should be created by an administrator or directly in the database.
 */
function create_user(string $username, string $email, string $password, string $role = 'student', bool $allowPrivileged = false): int
{
    global $conn;

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Default: only allow self-registration into non-privileged roles.
    // Privileged roles require an explicit allow flag.
    $allowed = $allowPrivileged
        ? ['student', 'technician', 'admin', 'staff']
        : ['student', 'technician'];

    if (!in_array($role, $allowed, true)) {
        $role = 'student';
    }
    $sql = 'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $username, $email, $passwordHash, $role);
    $stmt->execute();

    return (int)$conn->insert_id;
}
