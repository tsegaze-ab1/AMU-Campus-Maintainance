<?php
// includes/categories.php
// Category CRUD helpers (admin-managed).

require_once __DIR__ . '/db.php';

function list_categories(): array
{
    global $conn;

    $sql = 'SELECT id, name FROM categories ORDER BY name ASC';
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $stmt->bind_result($id, $name);

    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'id' => (int)$id,
            'name' => (string)$name,
        ];
    }

    return $items;
}

function find_category(int $categoryId): ?array
{
    global $conn;

    $sql = 'SELECT id, name FROM categories WHERE id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();

    $stmt->bind_result($id, $name);
    if (!$stmt->fetch()) {
        return null;
    }

    return [
        'id' => (int)$id,
        'name' => (string)$name,
    ];
}

function create_category(string $name): int
{
    global $conn;

    $sql = 'INSERT INTO categories (name) VALUES (?)';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $name);
    $stmt->execute();

    return (int)$conn->insert_id;
}

function update_category(int $categoryId, string $name): void
{
    global $conn;

    $sql = 'UPDATE categories SET name = ? WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $name, $categoryId);
    $stmt->execute();
}

function delete_category(int $categoryId): void
{
    global $conn;

    $sql = 'DELETE FROM categories WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
}
