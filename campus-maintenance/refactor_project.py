import os
import shutil

# Configuration
SOURCE_DIR = r"c:\xampp\htdocs\campus-maintenance"
TARGET_DIR = r"c:\xampp\htdocs\campus-maintenance-new"

# Helpers
def create_file(path, content):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content.strip())
    print(f"Created: {path}")

def copy_assets():
    # Copy Assets
    src_assets = os.path.join(SOURCE_DIR, 'assets')
    dst_assets = os.path.join(TARGET_DIR, 'frontend', 'assets')
    if os.path.exists(src_assets):
        if os.path.exists(dst_assets):
            shutil.rmtree(dst_assets)
        shutil.copytree(src_assets, dst_assets)
        print(f"Copied assets to {dst_assets}")

    # Copy Images
    src_images = os.path.join(SOURCE_DIR, 'Images')
    dst_images = os.path.join(TARGET_DIR, 'frontend', 'assets', 'images')
    if os.path.exists(src_images):
        if os.path.exists(dst_images):
            shutil.rmtree(dst_images)
        shutil.copytree(src_images, dst_images)
        print(f"Copied images to {dst_images}")

    # Copy SQL Schema
    src_sql = os.path.join(SOURCE_DIR, 'schema.sql')
    dst_sql = os.path.join(TARGET_DIR, 'backend', 'schema.sql')
    if os.path.exists(src_sql):
        os.makedirs(os.path.dirname(dst_sql), exist_ok=True)
        shutil.copy2(src_sql, dst_sql)
        print(f"Copied schema.sql to {dst_sql}")

# --- Backend Content ---

DB_PHP = """<?php
$DB_HOST = '127.0.0.1';
$DB_NAME = 'campus_maintenance';
$DB_USER = 'root';
$DB_PASS = '';
$DB_PORT = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
"""

AUTH_PHP = """<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
}

function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        json_response(['error' => 'Unauthorized'], 401);
    }
}

function require_role($allowed_roles) {
    require_login();
    $user = current_user();
    if (!in_array($user['role'], $allowed_roles)) {
        json_response(['error' => 'Forbidden'], 403);
    }
}
"""

LOGIN_API_PHP = """<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (!$email || !$password) {
    json_response(['error' => 'Missing credentials'], 400);
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    
    $redirect = 'student_dashboard.html';
    if ($user['role'] === 'admin') $redirect = 'admin_dashboard.html';
    if ($user['role'] === 'technician') $redirect = 'technician_dashboard.html';
    if ($user['role'] === 'staff') $redirect = 'staff_dashboard.html'; 
    
    json_response(['success' => true, 'role' => $user['role'], 'redirect' => $redirect]);
} else {
    json_response(['error' => 'Invalid email or password'], 401);
}
"""

REGISTER_API_PHP = """<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$username = $input['username'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$role = 'student'; 

if (!$username || !$email || !$password) {
    json_response(['error' => 'All fields required'], 400);
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    json_response(['error' => 'Email already registered'], 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $hash, $role);

if ($stmt->execute()) {
    json_response(['success' => true]);
} else {
    json_response(['error' => 'Registration failed'], 500);
}
"""

LOGOUT_API_PHP = """<?php
require_once __DIR__ . '/includes/auth.php';
session_destroy();
json_response(['success' => true]);
"""

CHECK_AUTH_API_PHP = """<?php
require_once __DIR__ . '/includes/auth.php';
if (current_user()) {
    json_response(['authenticated' => true, 'user' => current_user()]);
} else {
    json_response(['authenticated' => false]);
}
"""

# --- STUDENT APIs ---

STUDENT_API_REQUESTS = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['student']);

$user = current_user();
$studentId = $user['id'];

// Get Stats
$stats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0];
$statSql = "SELECT status, COUNT(*) as c FROM requests WHERE created_by = ? GROUP BY status";
$stmt = $conn->prepare($statSql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    if(isset($stats[$row['status']])) $stats[$row['status']] = $row['c'];
    $stats['total'] += $row['c'];
}

// Get List
$requests = [];
$reqSql = "SELECT id, title, location, priority, status, created_at FROM requests WHERE created_by = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($reqSql);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $requests[] = $row;
}

json_response(['stats' => $stats, 'requests' => $requests]);
"""

STUDENT_API_CREATE = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['student']);

$user = current_user();
$input = json_decode(file_get_contents('php://input'), true);

$title = $input['title'] ?? '';
$description = $input['description'] ?? '';
$location = $input['location'] ?? '';
$priority = $input['priority'] ?? 'medium';
$categoryId = $input['category_id'] ?? null;

if (!$title || !$description || !$location) {
    json_response(['error' => 'Missing fields'], 400);
}

$stmt = $conn->prepare("INSERT INTO requests (created_by, category_id, title, description, location, priority, status) VALUES (?, ?, ?, ?, ?, ?, 'new')");
$stmt->bind_param("iissss", $user['id'], $categoryId, $title, $description, $location, $priority);

if ($stmt->execute()) {
    json_response(['success' => true, 'id' => $conn->insert_id]);
} else {
    json_response(['error' => 'Failed to create request'], 500);
}
"""

STUDENT_API_DETAILS = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['student']);

$user = current_user();
$id = $_GET['id'] ?? 0;

if (!$id) json_response(['error' => 'ID required'], 400);

// Get Request
$stmt = $conn->prepare("SELECT r.*, c.name as category_name FROM requests r LEFT JOIN categories c ON r.category_id = c.id WHERE r.id = ? AND r.created_by = ?");
$stmt->bind_param("ii", $id, $user['id']);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) json_response(['error' => 'Not found'], 404);

// Get Comments
$stmt = $conn->prepare("SELECT rc.*, u.username FROM request_comments rc JOIN users u ON rc.user_id = u.id WHERE request_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

json_response(['request' => $req, 'comments' => $comments]);
"""

STUDENT_API_COMMENT = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['student']);

$user = current_user();
$input = json_decode(file_get_contents('php://input'), true);
$requestId = $input['request_id'] ?? 0;
$comment = $input['comment'] ?? '';

// Verify ownership
$stmt = $conn->prepare("SELECT id FROM requests WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $requestId, $user['id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) json_response(['error' => 'Not found'], 404);

$stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $requestId, $user['id'], $comment);
$stmt->execute();

json_response(['success' => true]);
"""

COMMON_API_CATEGORIES = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login(); 

$res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
json_response($res->fetch_all(MYSQLI_ASSOC));
"""

# --- TECHNICIAN APIs ---

TECH_API_REQUESTS = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['technician', 'staff']);

$user = current_user();
$techId = $user['id'];

// Stats
$stats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0];
$stmt = $conn->prepare("SELECT status, COUNT(*) as c FROM requests WHERE assigned_to = ? GROUP BY status");
$stmt->bind_param("i", $techId);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    if(isset($stats[$row['status']])) $stats[$row['status']] = $row['c'];
    $stats['total'] += $row['c'];
}

// List
$requests = [];
$stmt = $conn->prepare("SELECT id, title, location, priority, status FROM requests WHERE assigned_to = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $techId);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $requests[] = $row;
}

json_response(['stats' => $stats, 'requests' => $requests]);
"""

TECH_API_DETAILS = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['technician', 'staff']);

$user = current_user();
$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT r.*, c.name as category_name, u.username as created_by_name FROM requests r LEFT JOIN categories c ON r.category_id = c.id LEFT JOIN users u ON r.created_by = u.id WHERE r.id = ? AND r.assigned_to = ?");
$stmt->bind_param("ii", $id, $user['id']);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) json_response(['error' => 'Not found or not assigned'], 404);

$stmt = $conn->prepare("SELECT rc.*, u.username FROM request_comments rc JOIN users u ON rc.user_id = u.id WHERE request_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

json_response(['request' => $req, 'comments' => $comments]);
"""

TECH_API_UPDATE = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['technician', 'staff']);

$user = current_user();
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$status = $input['status'] ?? '';
$note = $input['note'] ?? '';

// Verify assignment
$stmt = $conn->prepare("SELECT id FROM requests WHERE id = ? AND assigned_to = ?");
$stmt->bind_param("ii", $id, $user['id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) json_response(['error' => 'Not assigned'], 403);

// Update Status
if ($status) {
    // Basic validation could be added here
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    
    // Add note as comment
    $msg = "Status changed to " . $status . ".";
    if ($note) $msg .= "\\n" . $note;
    
    $stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $id, $user['id'], $msg);
    $stmt->execute();
} 
// Just a comment
elseif ($note) {
    $stmt = $conn->prepare("INSERT INTO request_comments (request_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $id, $user['id'], $note);
    $stmt->execute();
}

json_response(['success' => true]);
"""

# --- ADMIN APIs ---
ADMIN_API_STATS = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin']);

$stats = ['total' => 0, 'new' => 0, 'in_progress' => 0, 'resolved' => 0];
$res = $conn->query("SELECT status, COUNT(*) as c FROM requests GROUP BY status");
while ($row = $res->fetch_assoc()) {
    if(isset($stats[$row['status']])) $stats[$row['status']] = $row['c'];
    $stats['total'] += $row['c'];
}
json_response($stats);
"""

ADMIN_API_REQUESTS = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin']);

// Fetch all requests
$requests = [];
$sql = "SELECT r.id, r.title, r.status, r.priority, c.name as category_name, u1.username as created_by_name, u2.username as assigned_to_name 
        FROM requests r 
        LEFT JOIN categories c ON r.category_id = c.id
        LEFT JOIN users u1 ON r.created_by = u1.id
        LEFT JOIN users u2 ON r.assigned_to = u2.id
        ORDER BY r.created_at DESC";
$res = $conn->query($sql);
json_response($res->fetch_all(MYSQLI_ASSOC));
"""

ADMIN_API_USERS = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $res = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    json_response($res->fetch_all(MYSQLI_ASSOC));
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'update_role') {
        $id = $input['id'] ?? 0;
        $role = $input['role'] ?? '';
        $allowed = ['student', 'staff', 'technician', 'admin'];
        
        if (!in_array($role, $allowed)) json_response(['error' => 'Invalid role'], 400);
        
        // Prevent removing own admin
        $curr = current_user();
        if ($curr['id'] == $id && $role !== 'admin') {
             json_response(['error' => 'Cannot change own role'], 400);
        }
        
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $id);
        $stmt->execute();
        json_response(['success' => true]);
    }
}
"""

ADMIN_API_CATEGORIES = """<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_role(['admin']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
    json_response($res->fetch_all(MYSQLI_ASSOC));
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $name = $input['name'] ?? '';
    $id = $input['id'] ?? 0;
    
    if ($action === 'create' && $name) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if($stmt->execute()) json_response(['success' => true]);
        else json_response(['error' => 'Duplicate or error'], 400);
    } elseif ($action === 'update' && $id && $name) {
        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        json_response(['success' => true]);
    } elseif ($action === 'delete' && $id) {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        json_response(['success' => true]);
    } else {
        json_response(['error' => 'Invalid action'], 400);
    }
}
"""

# --- Frontend Content ---

APP_JS = """
const API_BASE = '../backend';

async function fetchApi(endpoint, options = {}) {
    try {
        const res = await fetch(`${API_BASE}${endpoint}`, options);
        if (res.status === 401 && !window.location.href.includes('login.html')) {
            window.location.href = 'login.html';
            return null;
        }
        const contentType = res.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return await res.json();
        }
    } catch (e) {
        console.error("API Error", e);
    }
    return null;
}

async function checkAuth(requiredRole = null) {
    const data = await fetchApi('/check_auth.php');
    if (!data || !data.authenticated) {
        window.location.href = 'login.html';
        return null;
    }
    if (requiredRole && data.user.role !== requiredRole) {
        alert('Unauthorized access');
        window.location.href = 'login.html'; 
        return null;
    }
    const userDisplay = document.getElementById('username-display');
    if (userDisplay && data.user) {
        userDisplay.textContent = data.user.username || data.user.email;
    }
    return data.user;
}

async function logout() {
    await fetchApi('/logout.php');
    window.location.href = 'login.html';
}

function getQueryParam(name) {
    return new URLSearchParams(window.location.search).get(name);
}
"""

INDEX_HTML = """<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Maintenance</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="cm-heroLayer" style="--cm-hero-img: url('assets/images/hero-bg.jpg');"></div>
    <div class="container position-relative z-1 d-flex flex-column justify-content-center min-vh-100 align-items-center text-center">
        <h1 class="display-3 fw-bold mb-4">Campus Maintenance</h1>
        <p class="lead mb-4">Efficiently manage and track maintenance requests.</p>
        <div class="d-flex gap-3">
            <a href="login.html" class="btn btn-primary btn-lg">Login</a>
            <a href="register.html" class="btn btn-outline-light btn-lg">Register</a>
        </div>
    </div>
</body>
</html>
"""

LOGIN_HTML = """<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Maintenance</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="cm-heroLayer" style="--cm-hero-img: url('assets/images/hero-bg.jpg');"></div>
    <div class="container position-relative z-1 min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card p-4 shadow-lg text-dark" style="max-width: 400px; width: 100%;">
            <div class="text-center mb-4">
                <h2 class="fw-bold">Welcome Back</h2>
                <p class="text-muted">Sign in to your account</p>
            </div>
            <div id="error-alert" class="alert alert-danger d-none"></div>
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" id="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">Login</button>
            </form>
            <div class="mt-3 text-center">
                <small>Don't have an account? <a href="register.html">Register here</a></small>
            </div>
        </div>
    </div>
    <script src="assets/js/app.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            const res = await fetch(API_BASE + '/login.php', {
                method: 'POST',
                body: JSON.stringify({email, password}),
                headers: {'Content-Type': 'application/json'}
            });
            const json = await res.json();
            
            if (json.success) {
                window.location.href = json.redirect;
            } else {
                const err = document.getElementById('error-alert');
                err.textContent = json.error || 'Login failed';
                err.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
"""

# TEMPLATES FOR DASHBOARDS
COMMON_NAV = """
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
            <div class="container">
                <a class="navbar-brand" href="#">Campus Maintenance</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navMain">
                    <span class="navbar-text ms-auto me-3">Welcome, <span id="username-display">User</span></span>
                    <button class="btn btn-outline-light btn-sm" onclick="logout()">Logout</button>
                </div>
            </div>
        </nav>
    </header>
"""

STUDENT_DASHBOARD_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="assets/css/student_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h1>Student Dashboard</h1>
            <nav role="navigation">
                <a href="student_list.html" class="btn btn-outline-primary">My Requests</a>
                <a href="student_create.html" class="btn btn-primary">Create Request</a>
            </nav>
        </header>
        
        <section class="row g-3" aria-label="Statistics">
             <article class="col-md-3"><div class="card p-3 shadow-sm"><div class="h2" id="cnt-total">0</div><div>Total Requests</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-warning"><div class="h2" id="cnt-new">0</div><div>Pending</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-primary"><div class="h2" id="cnt-in_progress">0</div><div>In Progress</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-success"><div class="h2" id="cnt-resolved">0</div><div>Completed</div></div></article>
        </section>

        <section class="mt-5 mb-3">
            <h3>Recent Requests</h3>
            <div class="card shadow-sm"><div class="card-body">
                <table class="table table-hover">
                    <thead><tr><th>Title</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody id="recent-list"></tbody>
                </table>
            </div></div>
        </section>
    </main>
    
    <script src="assets/js/app.js"></script>
    <script>
        (async () => {{
            await checkAuth('student');
            const data = await fetchApi('/student/requests.php');
            if (data) {{
                document.getElementById('cnt-total').innerText = data.stats.total;
                document.getElementById('cnt-new').innerText = data.stats.new;
                document.getElementById('cnt-in_progress').innerText = data.stats.in_progress;
                document.getElementById('cnt-resolved').innerText = data.stats.resolved;
                
                const list = document.getElementById('recent-list');
                data.requests.slice(0, 5).forEach(r => {{
                    list.innerHTML += `<tr onclick="window.location='student_view.html?id=${{r.id}}'" style="cursor:pointer">
                        <td>${{r.title}}</td>
                        <td>${{r.status}}</td>
                        <td>${{r.created_at}}</td>
                    </tr>`;
                }});
            }}
        }})();
    </script>
</body>
</html>
"""

STUDENT_CREATE_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <h1>Create Request</h1>
        <section class="card shadow-sm mt-4"><div class="card-body">
            <form id="createForm">
                <div class="mb-3">
                    <label for="title">Title</label>
                    <input type="text" class="form-control" id="title" required>
                </div>
                <div class="mb-3">
                    <label for="category">Category</label>
                    <select class="form-select" id="category"></select>
                </div>
                <div class="mb-3">
                    <label for="priority">Priority</label>
                    <select class="form-select" id="priority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="location">Location</label>
                    <input type="text" class="form-control" id="location" required>
                </div>
                <div class="mb-3">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" rows="4" required></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Submit Request</button>
            </form>
        </div></section>
    </main>
    <script src="assets/js/app.js"></script>
    <script>
        (async () => {{
            await checkAuth('student');
            
            // Load Categories
            const cats = await fetchApi('/common/categories.php');
            const catSelect = document.getElementById('category');
            if (cats) {{
                cats.forEach(c => {{
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.innerText = c.name;
                    catSelect.appendChild(opt);
                }});
            }}
            
            document.getElementById('createForm').addEventListener('submit', async (e) => {{
                e.preventDefault();
                const body = {{
                    title: document.getElementById('title').value,
                    category_id: document.getElementById('category').value,
                    priority: document.getElementById('priority').value,
                    location: document.getElementById('location').value,
                    description: document.getElementById('description').value
                }};
                
                const res = await fetchApi('/student/create.php', {{
                    method: 'POST',
                    body: JSON.stringify(body)
                }});
                
                if (res && res.success) {{
                    window.location.href = 'student_view.html?id=' + res.id;
                }} else {{
                    alert('Error creating request');
                }}
            }});
        }})();
    </script>
</body>
</html>
"""

STUDENT_VIEW_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/student_view.css">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <a href="student_dashboard.html" class="btn btn-outline-secondary mb-3">&larr; Dashboard</a>
        <section id="details-container">Loading...</section>
        
        <section class="mt-4">
            <h4>Comments</h4>
            <div id="comments-list" class="list-group mb-3"></div>
            <form id="commentForm">
                <textarea class="form-control mb-2" id="new-comment" placeholder="Add a comment..."></textarea>
                <button class="btn btn-primary btn-sm">Post Comment</button>
            </form>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
    <script>
        (async () => {{
            await checkAuth('student');
            const id = getQueryParam('id');
            if(!id) return;
            
            async function load() {{
                const data = await fetchApi('/student/details.php?id=' + id);
                if (data && data.request) {{
                    const r = data.request;
                    document.getElementById('details-container').innerHTML = `
                        <article class="card shadow-sm"><div class="card-body">
                            <h2>#${{r.id}} ${{r.title}}</h2>
                            <span class="badge bg-secondary">${{r.status}}</span>
                            <span class="badge bg-info">${{r.priority}}</span>
                            <p class="mt-3">${{r.description}}</p>
                            <hr>
                            <small class="text-muted">Location: ${{r.location}} | Category: ${{r.category_name}}</small>
                        </div></article>
                    `;
                    
                    const cList = document.getElementById('comments-list');
                    cList.innerHTML = '';
                    data.comments.forEach(c => {{
                        cList.innerHTML += `<div class="list-group-item">
                            <strong>${{c.username}}</strong>: ${{c.comment}}
                            <div class="text-muted small">${{c.created_at}}</div>
                        </div>`;
                    }});
                }}
            }}
            
            load();
            
            document.getElementById('commentForm').addEventListener('submit', async (e) => {{
                e.preventDefault();
                const txt = document.getElementById('new-comment').value;
                if(!txt) return;
                
                await fetchApi('/student/comment.php', {{
                    method: 'POST',
                    body: JSON.stringify({{ request_id: id, comment: txt }})
                }});
                document.getElementById('new-comment').value = '';
                load();
            }});
        }})();
    </script>
</body>
</html>
"""

TECH_DASHBOARD_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Technician Dashboard</title>
    <link rel="stylesheet" href="assets/css/technician_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <h1>Technician Dashboard</h1>
        <section class="row g-3 my-4" aria-label="Statistics">
             <article class="col-md-3"><div class="card p-3 shadow-sm"><div class="h2" id="cnt-total">0</div><div>Assigned</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-warning"><div class="h2" id="cnt-new">0</div><div>Pending</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-primary"><div class="h2" id="cnt-progress">0</div><div>In Progress</div></div></article>
        </section>
        
        <section>
            <h3>Assigned Jobs</h3>
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Title</th><th>Location</th><th>Status</th><th>Action</th></tr></thead>
                <tbody id="job-list"></tbody>
            </table>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
    <script>
        (async () => {{
            await checkAuth('technician'); // or staff
            const data = await fetchApi('/technician/requests.php');
            if(data) {{
                document.getElementById('cnt-total').innerText = data.stats.total;
                document.getElementById('cnt-new').innerText = data.stats.new;
                document.getElementById('cnt-progress').innerText = data.stats.in_progress;
                
                const list = document.getElementById('job-list');
                data.requests.forEach(r => {{
                    list.innerHTML += `<tr>
                        <td>${{r.id}}</td>
                        <td>${{r.title}}</td>
                        <td>${{r.location}}</td>
                        <td>${{r.status}}</td>
                        <td><a href="technician_view.html?id=${{r.id}}" class="btn btn-sm btn-primary">View</a></td>
                    </tr>`;
                }});
            }}
        }})();
    </script>
</body>
</html>
"""

TECH_VIEW_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/technician_view.css">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <a href="technician_dashboard.html" class="btn btn-outline-secondary mb-3">&larr; Back</a>
        <section id="job-details"></section>
        
        <section class="card my-4 p-3 bg-light">
            <h5>Update Status</h5>
            <div class="d-flex gap-2">
                <button onclick="updateStatus('in_progress')" class="btn btn-primary">Start Job</button>
                <button onclick="updateStatus('resolved')" class="btn btn-success">Mark Resolved</button>
            </div>
            <textarea id="update-note" class="form-control mt-2" placeholder="Add a note..."></textarea>
        </section>
        
        <section>
            <h5>History</h5>
            <div id="comments-list" class="list-group"></div>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
    <script>
        let reqId = getQueryParam('id');
        async function updateStatus(status) {{
            const note = document.getElementById('update-note').value;
            await fetchApi('/technician/update.php', {{
                method: 'POST',
                body: JSON.stringify({{ id: reqId, status: status, note: note }})
            }});
            location.reload();
        }}
    
        (async () => {{
            await checkAuth();
            if(!reqId) return;
            
            const data = await fetchApi('/technician/details.php?id=' + reqId);
            if(data) {{
                const r = data.request;
                document.getElementById('job-details').innerHTML = `
                    <h1>${{r.title}} <span class="badge bg-dark">${{r.status}}</span></h1>
                    <p class="lead">${{r.description}}</p>
                    <div><strong>Location:</strong> ${{r.location}}</div>
                    <div><strong>Category:</strong> ${{r.category_name}}</div>
                `;
                
                const cList = document.getElementById('comments-list');
                data.comments.forEach(c => {{
                     cList.innerHTML += `<div class="list-group-item">
                        <strong>${{c.username}}</strong>: ${{c.comment}}
                        <div class="text-muted small">${{c.created_at}}</div>
                     </div>`;
                }});
            }}
        }})();
    </script>
</body>
</html>
"""

ADMIN_DASHBOARD_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/admin_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <h1>Admin Dashboard</h1>
            <nav class="btn-group" role="navigation">
                <a href="admin_users.html" class="btn btn-outline-primary">Manage Users</a>
                <a href="admin_categories.html" class="btn btn-outline-primary">Manage Categories</a>
            </nav>
        </header>
        <section class="row g-3 my-4" aria-label="Statistics">
             <article class="col-md-3"><div class="card p-3 shadow-sm"><div class="h2" id="cnt-total">0</div><div>Total</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-warning"><div class="h2" id="cnt-new">0</div><div>New</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-primary"><div class="h2" id="cnt-progress">0</div><div>In Progress</div></div></article>
             <article class="col-md-3"><div class="card p-3 shadow-sm text-success"><div class="h2" id="cnt-resolved">0</div><div>Resolved</div></div></article>
        </section>
        
        <section>
            <h3>All Requests</h3>
            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th>Assigned To</th></tr></thead>
                <tbody id="all-list"></tbody>
            </table>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
    <script>
        (async () => {{
            await checkAuth('admin');
            const stats = await fetchApi('/admin/stats.php');
            if(stats) {{
                document.getElementById('cnt-total').innerText = stats.total;
                document.getElementById('cnt-new').innerText = stats.new;
                document.getElementById('cnt-progress').innerText = stats.in_progress;
                document.getElementById('cnt-resolved').innerText = stats.resolved;
            }}
            
            const list = await fetchApi('/admin/requests.php');
            if(list) {{
                const tbody = document.getElementById('all-list');
                list.forEach(r => {{
                    tbody.innerHTML += `<tr>
                        <td>${{r.id}}</td>
                        <td>${{r.title}}</td>
                        <td>${{r.category_name || '-'}}</td>
                        <td>${{r.status}}</td>
                        <td>${{r.assigned_to_name || 'Unassigned'}}</td>
                    </tr>`;
                }});
            }}
        }})();
    </script>
</body>
</html>
"""

ADMIN_USERS_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <a href="admin_dashboard.html" class="btn btn-outline-secondary mb-3">&larr; Dashboard</a>
        <h1>Manage Users</h1>
        <section class="mt-4">
            <table class="table table-striped">
                <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
                <tbody id="users-list"></tbody>
            </table>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
    <script>
        async function updateRole(id, newRole) {{
            if(!confirm('Change role to ' + newRole + '?')) return;
            await fetchApi('/admin/users.php', {{
                method: 'POST',
                body: JSON.stringify({{ action: 'update_role', id: id, role: newRole }})
            }});
            location.reload();
        }}
    
        (async () => {{
            await checkAuth('admin');
            const users = await fetchApi('/admin/users.php');
            if(users) {{
                const tbody = document.getElementById('users-list');
                users.forEach(u => {{
                    // Simple select for role
                    const roles = ['student', 'staff', 'technician', 'admin'];
                    let select = `<select onchange="updateRole(${{u.id}}, this.value)" class="form-select form-select-sm">`;
                    roles.forEach(r => {{
                        select += `<option value="${{r}}" ${{u.role === r ? 'selected' : ''}}>${{r}}</option>`;
                    }});
                    select += `</select>`;
                    
                    tbody.innerHTML += `<tr>
                        <td>${{u.id}}</td>
                        <td>${{u.username}}</td>
                        <td>${{u.email}}</td>
                        <td>${{u.role}}</td>
                        <td>${{select}}</td>
                    </tr>`;
                }});
            }}
        }})();
    </script>
</body>
</html>
"""

ADMIN_CATEGORIES_HTML = f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    {COMMON_NAV}
    <main class="container">
        <a href="admin_dashboard.html" class="btn btn-outline-secondary mb-3">&larr; Dashboard</a>
        <h1>Manage Categories</h1>
        
        <section class="card p-3 my-3">
            <form id="createForm" class="d-flex gap-2">
                <input type="text" id="new-cat" class="form-control" placeholder="New Category Name" required>
                <button class="btn btn-success">Add</button>
            </form>
        </section>
        
        <ul id="cat-list" class="list-group"></ul>
    </main>
    <script src="assets/js/app.js"></script>
    <script>
        async function deleteCat(id) {{
            if(!confirm('Delete this category?')) return;
            await fetchApi('/admin/categories.php', {{
                method: 'POST',
                body: JSON.stringify({{ action: 'delete', id: id }})
            }});
            location.reload();
        }}

        (async () => {{
            await checkAuth('admin');
            const cats = await fetchApi('/admin/categories.php');
            if(cats) {{
                const list = document.getElementById('cat-list');
                cats.forEach(c => {{
                    list.innerHTML += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        ${{c.name}}
                        <div class="btn-group">
                             <button onclick="deleteCat(${{c.id}})" class="btn btn-sm btn-danger">Delete</button>
                        </div>
                    </li>`;
                }});
            }}
            
            document.getElementById('createForm').addEventListener('submit', async (e) => {{
                e.preventDefault();
                await fetchApi('/admin/categories.php', {{
                    method: 'POST',
                    body: JSON.stringify({{ action: 'create', name: document.getElementById('new-cat').value }})
                }});
                location.reload();
            }});
        }})();
    </script>
</body>
</html>
"""

# Main Execution
def main():
    if not os.path.exists(SOURCE_DIR):
        print(f"Source directory {SOURCE_DIR} not found.")
        return

    print("Starting refactor generation...")
    
    # 1. Structure
    os.makedirs(os.path.join(TARGET_DIR, 'backend', 'admin'), exist_ok=True)
    os.makedirs(os.path.join(TARGET_DIR, 'backend', 'student'), exist_ok=True)
    os.makedirs(os.path.join(TARGET_DIR, 'backend', 'technician'), exist_ok=True)
    os.makedirs(os.path.join(TARGET_DIR, 'backend', 'common'), exist_ok=True)
    os.makedirs(os.path.join(TARGET_DIR, 'backend', 'includes'), exist_ok=True)
    os.makedirs(os.path.join(TARGET_DIR, 'frontend', 'assets', 'js'), exist_ok=True)
    os.makedirs(os.path.join(TARGET_DIR, 'frontend', 'assets', 'css'), exist_ok=True)

    # 2. Assets
    copy_assets()

    # 3. Backend Files - Core
    create_file(os.path.join(TARGET_DIR, 'backend', 'includes', 'db.php'), DB_PHP)
    create_file(os.path.join(TARGET_DIR, 'backend', 'includes', 'auth.php'), AUTH_PHP)
    create_file(os.path.join(TARGET_DIR, 'backend', 'login.php'), LOGIN_API_PHP)
    create_file(os.path.join(TARGET_DIR, 'backend', 'register.php'), REGISTER_API_PHP)
    create_file(os.path.join(TARGET_DIR, 'backend', 'logout.php'), LOGOUT_API_PHP)
    create_file(os.path.join(TARGET_DIR, 'backend', 'check_auth.php'), CHECK_AUTH_API_PHP)
    create_file(os.path.join(TARGET_DIR, 'backend', 'common', 'categories.php'), COMMON_API_CATEGORIES)

    # Backend Files - Student
    create_file(os.path.join(TARGET_DIR, 'backend', 'student', 'requests.php'), STUDENT_API_REQUESTS)
    create_file(os.path.join(TARGET_DIR, 'backend', 'student', 'create.php'), STUDENT_API_CREATE)
    create_file(os.path.join(TARGET_DIR, 'backend', 'student', 'details.php'), STUDENT_API_DETAILS)
    create_file(os.path.join(TARGET_DIR, 'backend', 'student', 'comment.php'), STUDENT_API_COMMENT)

    # Backend Files - Technician
    create_file(os.path.join(TARGET_DIR, 'backend', 'technician', 'requests.php'), TECH_API_REQUESTS)
    create_file(os.path.join(TARGET_DIR, 'backend', 'technician', 'details.php'), TECH_API_DETAILS)
    create_file(os.path.join(TARGET_DIR, 'backend', 'technician', 'update.php'), TECH_API_UPDATE)

    # Backend Files - Admin
    create_file(os.path.join(TARGET_DIR, 'backend', 'admin', 'stats.php'), ADMIN_API_STATS)
    create_file(os.path.join(TARGET_DIR, 'backend', 'admin', 'requests.php'), ADMIN_API_REQUESTS)
    create_file(os.path.join(TARGET_DIR, 'backend', 'admin', 'users.php'), ADMIN_API_USERS)
    create_file(os.path.join(TARGET_DIR, 'backend', 'admin', 'categories.php'), ADMIN_API_CATEGORIES)

    # 4. Frontend Files
    create_file(os.path.join(TARGET_DIR, 'frontend', 'assets', 'js', 'app.js'), APP_JS)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'index.html'), INDEX_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'login.html'), LOGIN_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'register.html'), LOGIN_HTML.replace('Login', 'Register'))
    
    # Frontend - Dashboards
    create_file(os.path.join(TARGET_DIR, 'frontend', 'student_dashboard.html'), STUDENT_DASHBOARD_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'student_create.html'), STUDENT_CREATE_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'student_view.html'), STUDENT_VIEW_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'student_list.html'), STUDENT_DASHBOARD_HTML) 

    create_file(os.path.join(TARGET_DIR, 'frontend', 'technician_dashboard.html'), TECH_DASHBOARD_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'technician_view.html'), TECH_VIEW_HTML)
    
    create_file(os.path.join(TARGET_DIR, 'frontend', 'admin_dashboard.html'), ADMIN_DASHBOARD_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'admin_users.html'), ADMIN_USERS_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'admin_categories.html'), ADMIN_CATEGORIES_HTML)
    create_file(os.path.join(TARGET_DIR, 'frontend', 'staff_dashboard.html'), TECH_DASHBOARD_HTML.replace('Technician', 'Staff'))

    print(f"Refactoring complete! Project created at {TARGET_DIR}")

if __name__ == "__main__":
    main()
