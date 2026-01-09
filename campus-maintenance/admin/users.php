<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/admin.php';

require_role(['admin']);

$error = '';

$user = current_user();
$currentId = (int)($user['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $role = (string)($_POST['role'] ?? '');

    if (!$userId) {
        $error = 'Invalid user.';
    } elseif (!in_array($role, admin_allowed_roles(), true)) {
        $error = 'Invalid role.';
    } elseif ((int)$userId === $currentId && $role !== 'admin') {
        $error = 'You cannot remove your own admin role.';
    } else {
        update_user_role((int)$userId, $role);
        header('Location: ' . base_url('/admin/users.php'));
        exit;
    }
}

$users = list_users_basic();
$roles = admin_allowed_roles();

render_header('Admin - Users');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">Users</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!$users): ?>
            <p class="mb-0">No users found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo (int)$u['id']; ?></td>
                                <td><?php echo h($u['username']); ?></td>
                                <td><?php echo h($u['email']); ?></td>
                                <td>
                                    <form method="post" action="<?php echo h(base_url('/admin/users.php')); ?>" class="d-flex gap-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>" />
                                        <select name="role" class="form-select form-select-sm" style="max-width: 180px;">
                                            <?php foreach ($roles as $r): ?>
                                                <option value="<?php echo h($r); ?>" <?php echo ((string)$u['role'] === $r) ? 'selected' : ''; ?>><?php echo h($r); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                    </form>
                                </td>
                                <td><?php echo h($u['created_at']); ?></td>
                                <td class="text-end">
                                    <?php if ((int)$u['id'] === $currentId): ?>
                                        <span class="badge text-bg-secondary">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
render_footer();
