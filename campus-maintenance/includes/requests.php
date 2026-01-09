<?php
// includes/requests.php
// Request + comments helpers.

require_once __DIR__ . '/db.php';

function request_allowed_priorities(): array
{
    return ['low', 'medium', 'high'];
}

function request_allowed_statuses(): array
{
    return ['new', 'in_progress', 'resolved'];
}

function is_valid_priority(string $priority): bool
{
    return in_array($priority, request_allowed_priorities(), true);
}

function is_valid_status(string $status): bool
{
    return in_array($status, request_allowed_statuses(), true);
}

function create_request(
    int $createdBy,
    ?int $categoryId,
    string $title,
    string $description,
    string $location,
    string $priority
): int {
    global $conn;

    $status = 'new';
    $assignedTo = null;

    $sql = 'INSERT INTO requests (category_id, title, description, location, priority, status, created_by, assigned_to)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'isssssii',
        $categoryId,
        $title,
        $description,
        $location,
        $priority,
        $status,
        $createdBy,
        $assignedTo
    );
    $stmt->execute();

    return (int)$conn->insert_id;
}

function list_requests_for_student(int $studentId): array
{
    global $conn;

    $sql = 'SELECT r.id, r.title, r.location, r.priority, r.status, r.created_at, r.updated_at,
                   r.assigned_to, au.username, au.email
            FROM requests r
            LEFT JOIN users au ON au.id = r.assigned_to
            WHERE r.created_by = ?
            ORDER BY r.created_at DESC';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $studentId);
    $stmt->execute();

    $stmt->bind_result($id, $title, $location, $priority, $status, $createdAt, $updatedAt, $assignedTo, $assignedUsername, $assignedEmail);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'title' => (string)$title,
            'location' => (string)$location,
            'priority' => (string)$priority,
            'status' => (string)$status,
            'created_at' => (string)$createdAt,
            'updated_at' => (string)$updatedAt,
            'assigned_to' => $assignedTo === null ? null : (int)$assignedTo,
            'assigned_name' => $assignedUsername ? (string)$assignedUsername : ($assignedEmail ? (string)$assignedEmail : ''),
        ];
    }

    return $items;
}

function get_request_for_student(int $requestId, int $studentId): ?array
{
    global $conn;

    $sql = 'SELECT r.id, r.title, r.description, r.location, r.priority, r.status,
                   r.created_at, r.updated_at,
                   r.assigned_to, au.username, au.email,
                   r.category_id, c.name
            FROM requests r
            LEFT JOIN users au ON au.id = r.assigned_to
            LEFT JOIN categories c ON c.id = r.category_id
            WHERE r.id = ? AND r.created_by = ?
            LIMIT 1';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $requestId, $studentId);
    $stmt->execute();

    $stmt->bind_result(
        $id,
        $title,
        $description,
        $location,
        $priority,
        $status,
        $createdAt,
        $updatedAt,
        $assignedTo,
        $assignedUsername,
        $assignedEmail,
        $categoryId,
        $categoryName
    );

    if (!$stmt->fetch()) {
        return null;
    }

    return [
        'id' => (int)$id,
        'title' => (string)$title,
        'description' => (string)$description,
        'location' => (string)$location,
        'priority' => (string)$priority,
        'status' => (string)$status,
        'created_at' => (string)$createdAt,
        'updated_at' => (string)$updatedAt,
        'assigned_to' => $assignedTo === null ? null : (int)$assignedTo,
        'assigned_name' => $assignedUsername ? (string)$assignedUsername : ($assignedEmail ? (string)$assignedEmail : ''),
        'category_id' => $categoryId === null ? null : (int)$categoryId,
        'category_name' => $categoryName ? (string)$categoryName : '',
    ];
}

function get_request_by_id(int $requestId): ?array
{
    global $conn;

    $sql = 'SELECT r.id, r.title, r.description, r.location, r.priority, r.status,
                   r.created_at, r.updated_at,
                   r.created_by, cu.username, cu.email,
                   r.assigned_to, au.username, au.email,
                   r.category_id, c.name
            FROM requests r
            JOIN users cu ON cu.id = r.created_by
            LEFT JOIN users au ON au.id = r.assigned_to
            LEFT JOIN categories c ON c.id = r.category_id
            WHERE r.id = ?
            LIMIT 1';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $requestId);
    $stmt->execute();

    $stmt->bind_result(
        $id,
        $title,
        $description,
        $location,
        $priority,
        $status,
        $createdAt,
        $updatedAt,
        $createdBy,
        $createdUsername,
        $createdEmail,
        $assignedTo,
        $assignedUsername,
        $assignedEmail,
        $categoryId,
        $categoryName
    );

    if (!$stmt->fetch()) {
        return null;
    }

    return [
        'id' => (int)$id,
        'title' => (string)$title,
        'description' => (string)$description,
        'location' => (string)$location,
        'priority' => (string)$priority,
        'status' => (string)$status,
        'created_at' => (string)$createdAt,
        'updated_at' => (string)$updatedAt,
        'created_by' => (int)$createdBy,
        'created_by_name' => $createdUsername ? (string)$createdUsername : (string)$createdEmail,
        'assigned_to' => $assignedTo === null ? null : (int)$assignedTo,
        'assigned_to_name' => $assignedUsername ? (string)$assignedUsername : ($assignedEmail ? (string)$assignedEmail : ''),
        'category_id' => $categoryId === null ? null : (int)$categoryId,
        'category_name' => $categoryName ? (string)$categoryName : '',
    ];
}

function list_requests_assigned_to(int $technicianId): array
{
    global $conn;

    $sql = 'SELECT r.id, r.title, r.location, r.priority, r.status, r.created_at, r.updated_at,
                   cu.username, cu.email
            FROM requests r
            JOIN users cu ON cu.id = r.created_by
            WHERE r.assigned_to = ?
            ORDER BY r.updated_at DESC, r.created_at DESC';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $technicianId);
    $stmt->execute();

    $stmt->bind_result($id, $title, $location, $priority, $status, $createdAt, $updatedAt, $creatorUsername, $creatorEmail);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'title' => (string)$title,
            'location' => (string)$location,
            'priority' => (string)$priority,
            'status' => (string)$status,
            'created_at' => (string)$createdAt,
            'updated_at' => (string)$updatedAt,
            'created_by_name' => $creatorUsername ? (string)$creatorUsername : (string)$creatorEmail,
        ];
    }

    return $items;
}

function add_request_comment(int $requestId, int $userId, string $comment): int
{
    global $conn;

    $sql = 'INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $requestId, $userId, $comment);
    $stmt->execute();

    return (int)$conn->insert_id;
}

function list_request_comments(int $requestId): array
{
    global $conn;

    $sql = 'SELECT rc.id, rc.comment, rc.created_at, u.username, u.email, u.role
            FROM request_comments rc
            JOIN users u ON u.id = rc.user_id
            WHERE rc.request_id = ?
            ORDER BY rc.created_at ASC, rc.id ASC';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $requestId);
    $stmt->execute();

    $stmt->bind_result($id, $comment, $createdAt, $username, $email, $role);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'comment' => (string)$comment,
            'created_at' => (string)$createdAt,
            'user_name' => $username ? (string)$username : (string)$email,
            'user_role' => (string)$role,
        ];
    }

    return $items;
}

function assign_request(int $requestId, ?int $assignedTo): void
{
    global $conn;

    $sql = 'UPDATE requests SET assigned_to = ? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $assignedTo, $requestId);
    $stmt->execute();
}

function update_request_status(int $requestId, string $status): void
{
    global $conn;

    $sql = 'UPDATE requests SET status = ? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $requestId);
    $stmt->execute();
}

function update_request_status_if_assigned(int $requestId, int $technicianId, string $status): bool
{
    global $conn;

    $sql = 'UPDATE requests SET status = ? WHERE id = ? AND assigned_to = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $status, $requestId, $technicianId);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

function request_counts_for_student(int $studentId): array
{
    global $conn;

    $sql = 'SELECT status, COUNT(*) AS c FROM requests WHERE created_by = ? GROUP BY status';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $studentId);
    $stmt->execute();

    $stmt->bind_result($status, $count);

    $out = [
        'new' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'total' => 0,
    ];

    while ($stmt->fetch()) {
        $s = (string)$status;
        $c = (int)$count;
        if (isset($out[$s])) {
            $out[$s] = $c;
        }
        $out['total'] += $c;
    }

    return $out;
}

function request_counts_for_technician(int $technicianId): array
{
    global $conn;

    $sql = 'SELECT status, COUNT(*) AS c FROM requests WHERE assigned_to = ? GROUP BY status';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $technicianId);
    $stmt->execute();

    $stmt->bind_result($status, $count);

    $out = [
        'new' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'total' => 0,
    ];

    while ($stmt->fetch()) {
        $s = (string)$status;
        $c = (int)$count;
        if (isset($out[$s])) {
            $out[$s] = $c;
        }
        $out['total'] += $c;
    }

    return $out;
}

function request_counts_for_admin(): array
{
    global $conn;

    $sql = 'SELECT status, COUNT(*) AS c FROM requests GROUP BY status';
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmt->bind_result($status, $count);

    $out = [
        'new' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'total' => 0,
    ];

    while ($stmt->fetch()) {
        $s = (string)$status;
        $c = (int)$count;
        if (isset($out[$s])) {
            $out[$s] = $c;
        }
        $out['total'] += $c;
    }

    return $out;
}

function list_all_requests(): array
{
    global $conn;

    $sql = 'SELECT r.id, r.title, r.location, r.priority, r.status, r.created_at, r.updated_at,
                   cu.username, cu.email,
                   r.assigned_to, au.username, au.email,
                   c.name
            FROM requests r
            JOIN users cu ON cu.id = r.created_by
            LEFT JOIN users au ON au.id = r.assigned_to
            LEFT JOIN categories c ON c.id = r.category_id
            ORDER BY r.updated_at DESC, r.created_at DESC';

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmt->bind_result(
        $id,
        $title,
        $location,
        $priority,
        $status,
        $createdAt,
        $updatedAt,
        $creatorUsername,
        $creatorEmail,
        $assignedTo,
        $assignedUsername,
        $assignedEmail,
        $categoryName
    );

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'title' => (string)$title,
            'location' => (string)$location,
            'priority' => (string)$priority,
            'status' => (string)$status,
            'created_at' => (string)$createdAt,
            'updated_at' => (string)$updatedAt,
            'created_by_name' => $creatorUsername ? (string)$creatorUsername : (string)$creatorEmail,
            'assigned_to' => $assignedTo === null ? null : (int)$assignedTo,
            'assigned_to_name' => $assignedUsername ? (string)$assignedUsername : ($assignedEmail ? (string)$assignedEmail : ''),
            'category_name' => $categoryName ? (string)$categoryName : '',
        ];
    }

    return $items;
}
