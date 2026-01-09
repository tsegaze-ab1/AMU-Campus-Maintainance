<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/requests.php';
require_once __DIR__ . '/../includes/admin.php';

require_role(['admin']);

$user = current_user();
$adminId = (int)($user['id'] ?? 0);

$requestId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$requestId) {
    http_response_code(400);
    exit('Invalid request id.');
}

$error = '';

$request = get_request_by_id((int)$requestId);
if (!$request) {
    http_response_code(404);
    exit('Request not found.');
}

$assignees = list_technicians_and_staff();
$assigneeIds = array_map(static fn(array $u): int => (int)$u['id'], $assignees);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'assign') {
        $assignedRaw = $_POST['assigned_to'] ?? '';
        if ($assignedRaw === '' || $assignedRaw === null) {
            $assignedTo = null;
        } else {
            $assignedTo = filter_var($assignedRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        }

        if ($assignedTo !== null && !in_array((int)$assignedTo, $assigneeIds, true)) {
            $error = 'Invalid assignee.';
        } else {
            assign_request((int)$requestId, $assignedTo);

            $who = 'Unassigned';
            foreach ($assignees as $a) {
                if ((int)$a['id'] === (int)$assignedTo) {
                    $who = ($a['username'] ?: $a['email']) . ' (' . $a['role'] . ')';
                    break;
                }
            }
            add_request_comment((int)$requestId, $adminId, 'Assignment updated by admin: ' . $who . '.');

            header('Location: ' . base_url('/admin/view.php?id=' . (int)$requestId));
            exit;
        }
    } elseif ($action === 'status') {
        $status = trim((string)($_POST['status'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));

        if ($status === '' || !is_valid_status($status)) {
            $error = 'Invalid status.';
        } elseif (mb_strlen($note) > 2000) {
            $error = 'Note is too long.';
        } else {
            update_request_status((int)$requestId, $status);
            $msg = $note !== ''
                ? "Status set to {$status} by admin.\n\n" . $note
                : "Status set to {$status} by admin.";
            add_request_comment((int)$requestId, $adminId, $msg);

            header('Location: ' . base_url('/admin/view.php?id=' . (int)$requestId));
            exit;
        }
    } elseif ($action === 'comment') {
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($comment === '') {
            $error = 'Comment cannot be empty.';
        } elseif (mb_strlen($comment) > 2000) {
            $error = 'Comment is too long.';
        } else {
            add_request_comment((int)$requestId, $adminId, $comment);
            header('Location: ' . base_url('/admin/view.php?id=' . (int)$requestId));
            exit;
        }
    } else {
        $error = 'Invalid action.';
    }

    $request = get_request_by_id((int)$requestId) ?: $request;
}

$comments = list_request_comments((int)$requestId);

render_header('Admin - Request #' . (int)$requestId);
?>
<link rel="stylesheet" href="<?php echo h(base_url('/assets/css/admin_view.css')); ?>" />
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 m-0">Request #<?php echo (int)$request['id']; ?></h1>
        <div class="text-muted"><?php echo h($request['title']); ?></div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo h(base_url('/admin/requests.php')); ?>">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="mb-2"><span class="text-muted">Status:</span> <strong><?php echo h($request['status']); ?></strong></div>
                <div class="mb-2"><span class="text-muted">Priority:</span> <strong><?php echo h($request['priority']); ?></strong></div>
                <div class="mb-2"><span class="text-muted">Category:</span> <strong><?php echo $request['category_name'] ? h($request['category_name']) : '—'; ?></strong></div>
                <div class="mb-2"><span class="text-muted">Assigned:</span> <strong><?php echo $request['assigned_to_name'] ? h($request['assigned_to_name']) : 'Unassigned'; ?></strong></div>
                <div class="mb-2"><span class="text-muted">Created By:</span> <strong><?php echo h($request['created_by_name']); ?></strong></div>
                <div class="mb-2"><span class="text-muted">Location:</span> <strong><?php echo h($request['location']); ?></strong></div>
                <div class="mb-2"><span class="text-muted">Created:</span> <strong><?php echo h($request['created_at']); ?></strong></div>
                <div class="mb-0"><span class="text-muted">Updated:</span> <strong><?php echo h($request['updated_at']); ?></strong></div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h6">Description</h2>
                <p class="mb-0 cm-prewrap"><?php echo h($request['description']); ?></p>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h6">Assign</h2>
                <form method="post" action="<?php echo h(base_url('/admin/view.php?id=' . (int)$requestId)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="assign" />

                    <div class="mb-2">
                        <label class="form-label" for="admin_assign_to">Assign to</label>
                        <select id="admin_assign_to" name="assigned_to" class="form-select">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($assignees as $a): ?>
                                <option value="<?php echo (int)$a['id']; ?>" <?php echo ((int)($request['assigned_to'] ?? 0) === (int)$a['id']) ? 'selected' : ''; ?>>
                                    <?php echo h(($a['username'] ?: $a['email']) . ' (' . $a['role'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="btn btn-primary" type="submit">Save Assignment</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h6">Set Status</h2>
                <form method="post" action="<?php echo h(base_url('/admin/view.php?id=' . (int)$requestId)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="status" />

                    <div class="mb-2">
                        <label class="form-label" for="admin_status">Status</label>
                        <select id="admin_status" name="status" class="form-select" required>
                            <?php foreach (request_allowed_statuses() as $s): ?>
                                <option value="<?php echo h($s); ?>" <?php echo ((string)$request['status'] === $s) ? 'selected' : ''; ?>><?php echo h($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label" for="admin_note">Note (optional)</label>
                        <textarea id="admin_note" name="note" class="form-control" rows="3" placeholder="Optional note..."></textarea>
                    </div>

                    <button class="btn btn-outline-primary" type="submit">Update Status</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6">Updates / Comments</h2>

                <form method="post" action="<?php echo h(base_url('/admin/view.php?id=' . (int)$requestId)); ?>" class="mb-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="comment" />

                    <div class="mb-2">
                        <textarea name="comment" class="form-control" rows="3" placeholder="Add an admin note..." required></textarea>
                    </div>
                    <button class="btn btn-outline-primary" type="submit">Add Comment</button>
                </form>

                <?php if (!$comments): ?>
                    <p class="text-muted mb-0">No comments yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($comments as $c): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo h($c['user_name']); ?></strong>
                                    <span class="text-muted small"><?php echo h($c['created_at']); ?></span>
                                </div>
                                <div class="small text-muted mb-1"><?php echo h($c['user_role']); ?></div>
                                <div class="cm-prewrap"><?php echo h($c['comment']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
