<?php
// includes/user.php
// Database helpers related to user accounts.
// All queries use MySQLi prepared statements.

require_once __DIR__ . '/db.php';

/**
 * Check users-table columns once and cache the result.
 */
function users_table_has_column(string $column): bool
{
    static $cache = [];

    if (isset($cache[$column])) {
        return $cache[$column];
    }

    global $conn;

    $columnEscaped = mysqli_real_escape_string($conn, $column);
    $sql = "SHOW COLUMNS FROM users LIKE '" . $columnEscaped . "'";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        $cache[$column] = false;
        return false;
    }

    $exists = mysqli_num_rows($result) > 0;
    $cache[$column] = $exists;

    return $exists;
}

/**
 * Check table existence once and cache the result.
 */
function table_exists(string $table): bool
{
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    global $conn;

    $tableEscaped = mysqli_real_escape_string($conn, $table);
    $sql = "SHOW TABLES LIKE '" . $tableEscaped . "'";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        $cache[$table] = false;
        return false;
    }

    $exists = mysqli_num_rows($result) > 0;
    $cache[$table] = $exists;

    return $exists;
}

/**
 * Roles that admin can manage in the user management module.
 */
function role_management_allowed_roles(): array
{
    return ['student', 'technician', 'admin', 'admin2'];
}

/**
 * Build a safe SQL expression that returns the stored password value regardless of schema version.
 */
function users_password_value_expr(): string
{
    $hasPassword = users_table_has_column('password');
    $hasPasswordHash = users_table_has_column('password_hash');

    if ($hasPassword && $hasPasswordHash) {
        return "COALESCE(NULLIF(password, ''), password_hash)";
    }

    if ($hasPassword) {
        return 'password';
    }

    if ($hasPasswordHash) {
        return 'password_hash';
    }

    return "''";
}

/**
 * Find a user row by email.
 *
 * @return array|null Returns associative array if found, otherwise null.
 */
function find_user_by_email(string $email): ?array
{
    global $conn;

    $passwordExpr = users_password_value_expr();
    $sql = 'SELECT id, username, email, ' . $passwordExpr . ' AS password_value, role FROM users WHERE email = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();

    // Use bind_result instead of get_result() for broader compatibility.
    $stmt->bind_result($id, $username, $emailOut, $passwordValue, $role);
    if (!$stmt->fetch()) {
        return null;
    }

    return [
        'id' => (int)$id,
        'username' => (string)$username,
        'email' => (string)$emailOut,
        'password_hash' => (string)$passwordValue,
        'role' => (string)$role,
    ];
}

/**
 * Find a user row by username for the single-login gateway.
 * Reads password from `password` and supports fallback to legacy `password_hash`.
 *
 * @return array|null Returns associative array if found, otherwise null.
 */
function find_user_by_username(string $username): ?array
{
    global $conn;

    $passwordExpr = users_password_value_expr();

    $sql = 'SELECT id, username, email, ' . $passwordExpr . ' AS password_value, role FROM users WHERE username = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();

    $stmt->bind_result($id, $usernameOut, $emailOut, $passwordValue, $role);
    if (!$stmt->fetch()) {
        return null;
    }

    return [
        'id' => (int)$id,
        'username' => (string)$usernameOut,
        'email' => (string)$emailOut,
        'password' => (string)$passwordValue,
        'role' => (string)$role,
    ];
}

/**
 * Find a user by username OR email for compatibility login.
 * Keeps single login form while supporting legacy email habits.
 */
function find_user_for_login(string $identifier): ?array
{
    global $conn;

    $hasPasswordColumn = users_table_has_column('password');
    $hasPasswordHashColumn = users_table_has_column('password_hash');

    if ($hasPasswordColumn && $hasPasswordHashColumn) {
        $sql = 'SELECT id, username, email, password AS password_primary, password_hash AS password_secondary, role FROM users WHERE username = ? OR email = ? LIMIT 1';
    } elseif ($hasPasswordColumn) {
        $sql = 'SELECT id, username, email, password AS password_primary, NULL AS password_secondary, role FROM users WHERE username = ? OR email = ? LIMIT 1';
    } elseif ($hasPasswordHashColumn) {
        $sql = 'SELECT id, username, email, password_hash AS password_primary, NULL AS password_secondary, role FROM users WHERE username = ? OR email = ? LIMIT 1';
    } else {
        return null;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();

    $stmt->bind_result($id, $usernameOut, $emailOut, $passwordPrimary, $passwordSecondary, $role);
    $found = $stmt->fetch();

    if (!$found) {
        // Fallback for strict/case-sensitive collations on some shared hosts.
        $sqlFallback = str_replace('WHERE username = ? OR email = ?', 'WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)', $sql);
        $stmtFallback = $conn->prepare($sqlFallback);
        if (!$stmtFallback) {
            return null;
        }

        $stmtFallback->bind_param('ss', $identifier, $identifier);
        $stmtFallback->execute();
        $stmtFallback->bind_result($id, $usernameOut, $emailOut, $passwordPrimary, $passwordSecondary, $role);
        if (!$stmtFallback->fetch()) {
            return null;
        }
    }

    $primary = trim((string)$passwordPrimary);
    $secondary = trim((string)$passwordSecondary);
    $selected = $primary !== '' ? $primary : $secondary;

    return [
        'id' => (int)$id,
        'username' => (string)$usernameOut,
        'email' => (string)$emailOut,
        'password' => $selected,
        'password_alt' => $secondary !== '' ? $secondary : $primary,
        'role' => (string)$role,
    ];
}

/**
 * Update stored password fields for a user with a modern hash.
 */
function upgrade_user_password_hash(int $userId, string $passwordHash): bool
{
    global $conn;

    if ($userId <= 0 || $passwordHash === '') {
        return false;
    }

    if (users_table_has_column('password')) {
        $sql = users_table_has_column('password_hash')
            ? 'UPDATE users SET password = ?, password_hash = ? WHERE id = ?'
            : 'UPDATE users SET password = ? WHERE id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        if (users_table_has_column('password_hash')) {
            $stmt->bind_param('ssi', $passwordHash, $passwordHash, $userId);
        } else {
            $stmt->bind_param('si', $passwordHash, $userId);
        }

        return $stmt->execute();
    }

    if (users_table_has_column('password_hash')) {
        $sql = 'UPDATE users SET password_hash = ? WHERE id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('si', $passwordHash, $userId);
        return $stmt->execute();
    }

    return false;
}

/**
 * Verify login password with compatibility for legacy stored values.
 * Supported legacy formats are upgraded to PASSWORD_DEFAULT hash on success.
 */
function verify_login_password(array $user, string $inputPassword): bool
{
    $storedPrimary = trim((string)($user['password'] ?? ''));
    $storedAlt = trim((string)($user['password_alt'] ?? ''));
    $storedCandidates = array_values(array_filter([$storedPrimary, $storedAlt], static function ($v) {
        return $v !== '';
    }));

    if (empty($storedCandidates) || $inputPassword === '') {
        return false;
    }

    foreach ($storedCandidates as $stored) {
        // Preferred path: modern password hash.
        if (password_verify($inputPassword, $stored)) {
            if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
                if ($newHash !== false) {
                    upgrade_user_password_hash((int)($user['id'] ?? 0), $newHash);
                }
            }
            return true;
        }

        // Legacy compatibility: plaintext, md5, sha1.
        $storedLower = strtolower($stored);
        $legacyMatched = hash_equals($stored, $inputPassword)
            || hash_equals($storedLower, md5($inputPassword))
            || hash_equals($storedLower, sha1($inputPassword));

        if ($legacyMatched) {
            $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
            if ($newHash !== false) {
                upgrade_user_password_hash((int)($user['id'] ?? 0), $newHash);
            }

            return true;
        }
    }

    return false;
}

/**
 * Ensure a default admin account exists for first deployment.
 * Credentials requested by project owner:
 * - username/email: Tsegaabk@gmail.com
 * - password: 12341234
 */
function ensure_default_admin_user(): void
{
    global $conn;

    $adminIdentifier = 'Tsegaabk@gmail.com';
    $adminPassword = '12341234';
    $adminRole = 'admin';

    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        return;
    }

    $existing = find_user_by_username($adminIdentifier);
    if (!$existing) {
        $existing = find_user_by_email($adminIdentifier);
    }

    $hasPasswordColumn = users_table_has_column('password');
    $hasPasswordHashColumn = users_table_has_column('password_hash');

    if ($existing && (int)($existing['id'] ?? 0) > 0) {
        // Existing account found: ensure requested first-admin credentials are applied.
        $userId = (int)$existing['id'];

        if ($hasPasswordColumn && $hasPasswordHashColumn) {
            $sql = 'UPDATE users SET username = ?, email = ?, role = ?, password = ?, password_hash = ? WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return;
            }

            $stmt->bind_param('sssssi', $adminIdentifier, $adminIdentifier, $adminRole, $passwordHash, $passwordHash, $userId);
            $stmt->execute();
            return;
        }

        if ($hasPasswordColumn) {
            $sql = 'UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return;
            }

            $stmt->bind_param('ssssi', $adminIdentifier, $adminIdentifier, $adminRole, $passwordHash, $userId);
            $stmt->execute();
            return;
        }

        if ($hasPasswordHashColumn) {
            $sql = 'UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return;
            }

            $stmt->bind_param('ssssi', $adminIdentifier, $adminIdentifier, $adminRole, $passwordHash, $userId);
            $stmt->execute();
        }

        return;
    }

    if ($hasPasswordColumn && $hasPasswordHashColumn) {
        $sql = 'INSERT INTO users (username, email, password, password_hash, role) VALUES (?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sssss', $adminIdentifier, $adminIdentifier, $passwordHash, $passwordHash, $adminRole);
        $stmt->execute();
        return;
    }

    if ($hasPasswordColumn) {
        $sql = 'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ssss', $adminIdentifier, $adminIdentifier, $passwordHash, $adminRole);
        $stmt->execute();
        return;
    }

    if ($hasPasswordHashColumn) {
        $sql = 'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ssss', $adminIdentifier, $adminIdentifier, $passwordHash, $adminRole);
        $stmt->execute();
    }
}

/**
 * Create a new user.
 *
 * Note: role is intentionally defaulted to 'student' to avoid privilege escalation.
 * Admin accounts should be created by an administrator or directly in the database.
 */
function create_user(string $username, string $email, string $password, string $role = 'student', bool $allowPrivileged = false): int
{
    global $conn;

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Default: only allow self-registration into non-privileged roles.
    // Privileged roles require an explicit allow flag.
    $allowed = $allowPrivileged
        ? ['student', 'technician', 'admin']
        : ['student', 'technician'];

    if (!in_array($role, $allowed, true)) {
        $role = 'student';
    }
    $hasPasswordColumn = users_table_has_column('password');
    $hasPasswordHashColumn = users_table_has_column('password_hash');

    if ($hasPasswordColumn && $hasPasswordHashColumn) {
        $sql = 'INSERT INTO users (username, email, password, password_hash, role) VALUES (?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('sssss', $username, $email, $passwordHash, $passwordHash, $role);
        if (!$stmt->execute()) {
            return 0;
        }
    } elseif ($hasPasswordColumn) {
        $sql = 'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ssss', $username, $email, $passwordHash, $role);
        if (!$stmt->execute()) {
            return 0;
        }
    } elseif ($hasPasswordHashColumn) {
        $sql = 'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ssss', $username, $email, $passwordHash, $role);
        if (!$stmt->execute()) {
            return 0;
        }
    } else {
        return 0;
    }

    return (int)$conn->insert_id;
}

/**
 * Create user from admin user-management page.
 */
function create_user_for_role_management(string $username, string $password, string $role): bool
{
    global $conn;

    if (!in_array($role, role_management_allowed_roles(), true)) {
        return false;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Keep compatibility with existing schema that still has email/password_hash columns.
    $email = $username . '@local.user';
    $hasPasswordColumn = users_table_has_column('password');
    $hasPasswordHashColumn = users_table_has_column('password_hash');

    if ($hasPasswordColumn && $hasPasswordHashColumn) {
        $sql = 'INSERT INTO users (username, email, password, password_hash, role) VALUES (?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sssss', $username, $email, $passwordHash, $passwordHash, $role);
        return $stmt->execute();
    }

    if ($hasPasswordColumn) {
        $sql = 'INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssss', $username, $email, $passwordHash, $role);
        return $stmt->execute();
    }

    if (!$hasPasswordHashColumn) {
        return false;
    }

    $sql = 'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ssss', $username, $email, $passwordHash, $role);
    return $stmt->execute();
}

/**
 * Update role-management user details.
 */
function update_user_for_role_management(int $userId, string $username, string $role, ?string $newPassword = null): bool
{
    global $conn;

    if (!in_array($role, role_management_allowed_roles(), true)) {
        return false;
    }

    $email = $username . '@local.user';

    $hasPasswordColumn = users_table_has_column('password');
    $hasPasswordHashColumn = users_table_has_column('password_hash');

    if ($newPassword !== null && $newPassword !== '') {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hasPasswordColumn && $hasPasswordHashColumn) {
            $sql = 'UPDATE users SET username = ?, email = ?, role = ?, password = ?, password_hash = ? WHERE id = ?';
        } elseif ($hasPasswordColumn) {
            $sql = 'UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?';
        } elseif ($hasPasswordHashColumn) {
            $sql = 'UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?';
        } else {
            return false;
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        if ($hasPasswordColumn && $hasPasswordHashColumn) {
            $stmt->bind_param('sssssi', $username, $email, $role, $passwordHash, $passwordHash, $userId);
        } elseif ($hasPasswordColumn || $hasPasswordHashColumn) {
            $stmt->bind_param('ssssi', $username, $email, $role, $passwordHash, $userId);
        } else {
            return false;
        }

        return $stmt->execute();
    }

    $sql = 'UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sssi', $username, $email, $role, $userId);
    return $stmt->execute();
}

/**
 * Delete user from role-management page.
 */
function delete_user_for_role_management(int $userId, int $fallbackOwnerId = 0): bool
{
    global $conn;

    if ($fallbackOwnerId <= 0) {
        return false;
    }

    $txStarted = false;
    if (method_exists($conn, 'begin_transaction')) {
        $txStarted = $conn->begin_transaction();
    } else {
        $txStarted = $conn->query('START TRANSACTION') === true;
    }

    try {
        // Keep request history valid before deleting the user.
        $sqlAssigned = 'UPDATE requests SET assigned_to = NULL WHERE assigned_to = ?';
        $stmtAssigned = $conn->prepare($sqlAssigned);
        if ($stmtAssigned) {
            $stmtAssigned->bind_param('i', $userId);
            if (!$stmtAssigned->execute()) {
                throw new RuntimeException('Failed to cleanup assigned requests.');
            }
        } elseif ((int)$conn->errno !== 1146) {
            throw new RuntimeException('Failed to prepare assigned_to cleanup.');
        }

        $sqlCreated = 'UPDATE requests SET created_by = ? WHERE created_by = ?';
        $stmtCreated = $conn->prepare($sqlCreated);
        if ($stmtCreated) {
            $stmtCreated->bind_param('ii', $fallbackOwnerId, $userId);
            if (!$stmtCreated->execute()) {
                throw new RuntimeException('Failed to reassign request ownership.');
            }
        } elseif ((int)$conn->errno !== 1146) {
            throw new RuntimeException('Failed to prepare request owner reassignment.');
        }

        $sqlComments = 'DELETE FROM request_comments WHERE user_id = ?';
        $stmtComments = $conn->prepare($sqlComments);
        if ($stmtComments) {
            $stmtComments->bind_param('i', $userId);
            if (!$stmtComments->execute()) {
                throw new RuntimeException('Failed to delete user comments.');
            }
        } elseif ((int)$conn->errno !== 1146) {
            throw new RuntimeException('Failed to prepare comment cleanup.');
        }

        $sqlAttachments = 'DELETE FROM request_attachments WHERE user_id = ?';
        $stmtAttachments = $conn->prepare($sqlAttachments);
        if ($stmtAttachments) {
            $stmtAttachments->bind_param('i', $userId);
            if (!$stmtAttachments->execute()) {
                throw new RuntimeException('Failed to delete user attachments.');
            }
        } elseif ((int)$conn->errno !== 1146) {
            throw new RuntimeException('Failed to prepare attachment cleanup.');
        }

        $sql = 'DELETE FROM users WHERE id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare user delete.');
        }

        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            throw new RuntimeException('Failed to delete user.');
        }

        if ($txStarted) {
            $conn->commit();
        }
        return true;
    } catch (Throwable $e) {
        if ($txStarted) {
            $conn->rollback();
        }
        return false;
    }
}

/**
 * List users for role-management page.
 */
function list_role_management_users(): array
{
    global $conn;

    $sql = 'SELECT id, username, role, created_at FROM users ORDER BY created_at DESC, id DESC';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->execute();
    $stmt->bind_result($id, $username, $role, $createdAt);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'username' => (string)$username,
            'role' => (string)$role,
            'created_at' => (string)$createdAt,
        ];
    }

    return $items;
}
