<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/user.php';

require_role(['admin']);

$user = current_user();
$currentId = (int)($user['id'] ?? 0);
$users = list_role_management_users();

$success = trim((string)($_GET['success'] ?? ''));
$error = trim((string)($_GET['error'] ?? ''));

$urlDashboard = base_url('/admin/dashboard.php');
$urlRequests = base_url('/admin/requests.php');
$urlCategories = base_url('/admin/categories.php');
$urlUsers = base_url('/admin/user_management.php');
$urlLogout = base_url('/logout.php');
$urlActions = base_url('/admin/add_user.php');

$GLOBALS['CM_HIDE_HEADER_NAV_LINKS'] = true;

render_header('Admin - User Management');
?>
<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
            Menu
        </button>
        <div>
            <h1 class="h4 m-0">User Management</h1>
            <div class="text-muted small">Create, update, and delete all system users and roles.</div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="adminSidebarLabel">Admin Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group">
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlRequests); ?>">Requests</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlCategories); ?>">Categories</a>
            <a class="list-group-item list-group-item-action active" href="<?php echo h($urlUsers); ?>">Users</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlLogout); ?>">Logout</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-3 d-none d-lg-block">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="fw-semibold mb-2">Navigation</div>
                <nav class="nav nav-pills flex-column gap-1">
                    <a class="nav-link" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
                    <a class="nav-link" href="<?php echo h($urlRequests); ?>">Requests</a>
                    <a class="nav-link" href="<?php echo h($urlCategories); ?>">Categories</a>
                    <a class="nav-link active" href="<?php echo h($urlUsers); ?>">Users</a>
                    <a class="nav-link" href="<?php echo h($urlLogout); ?>">Logout</a>
                </nav>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="alert"><?php echo h($success); ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Add New User</h2>
                <form method="post" action="<?php echo h($urlActions); ?>" class="row g-2">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="add" />
                    <div class="col-12 col-md-4">
                        <input type="text" name="username" class="form-control" placeholder="Username" required />
                    </div>
                    <div class="col-12 col-md-4">
                        <input type="password" name="password" class="form-control" placeholder="Password" required />
                    </div>
                    <div class="col-12 col-md-3">
                        <select name="role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="technician">Technician</option>
                            <option value="student">Student</option>
                            <option value="admin2">Admin2</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-1 d-grid">
                        <button class="btn btn-primary" type="submit">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">User List</h2>

                <?php if (!$users): ?>
                    <div class="alert alert-light border mb-0" role="alert">No users found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">ID</th>
                                    <th>Username</th>
                                    <th style="width: 170px;">Role</th>
                                    <th style="width: 160px;">Created</th>
                                    <th style="width: 320px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo (int)$u['id']; ?></td>
                                        <td><?php echo h($u['username']); ?></td>
                                        <td><span class="badge text-bg-secondary"><?php echo h($u['role']); ?></span></td>
                                        <td class="text-muted small"><?php echo h($u['created_at']); ?></td>
                                        <td>
                                            <form method="post" action="<?php echo h($urlActions); ?>" class="row g-2 align-items-center">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="edit" />
                                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>" />
                                                <div class="col-12 col-md-4">
                                                    <input type="text" name="username" class="form-control form-control-sm" value="<?php echo h($u['username']); ?>" required />
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <select name="role" class="form-select form-select-sm">
                                                        <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        <option value="technician" <?php echo $u['role'] === 'technician' ? 'selected' : ''; ?>>Technician</option>
                                                        <option value="student" <?php echo $u['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                                        <option value="admin2" <?php echo $u['role'] === 'admin2' ? 'selected' : ''; ?>>Admin2</option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <input type="password" name="new_password" class="form-control form-control-sm" placeholder="New password" />
                                                </div>
                                                <div class="col-6 col-md-1 d-grid">
                                                    <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                                </div>
                                            </form>
                                            <form method="post" action="<?php echo h($urlActions); ?>" class="mt-1">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete" />
                                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>" />
                                                <button class="btn btn-sm btn-outline-danger" type="submit" <?php echo ((int)$u['id'] === $currentId) ? 'disabled' : ''; ?>>Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
