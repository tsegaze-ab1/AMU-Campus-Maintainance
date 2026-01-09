<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/requests.php';

require_role(['technician', 'staff']);

$user = current_user();
$techId = (int)($user['id'] ?? 0);

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

if ((int)($request['assigned_to'] ?? 0) !== $techId) {
    http_response_code(403);
    exit('Access denied.');
}

function allowed_next_statuses(string $current): array
{
    if ($current === 'new') {
        return ['in_progress'];
    }
    if ($current === 'in_progress') {
        return ['resolved'];
    }
    return [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_with_csrf();

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'status') {
        $next = trim((string)($_POST['status'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));

        $current = (string)$request['status'];
        $allowed = allowed_next_statuses($current);

        if ($next === '' || !is_valid_status($next)) {
            $error = 'Invalid status.';
        } elseif (!in_array($next, $allowed, true)) {
            $error = 'Invalid status transition.';
        } elseif (mb_strlen($note) > 2000) {
            $error = 'Note is too long.';
        } else {
            $ok = update_request_status_if_assigned((int)$requestId, $techId, $next);
            if (!$ok) {
                http_response_code(403);
                exit('Access denied.');
            }

            if ($note !== '') {
                add_request_comment((int)$requestId, $techId, "Status changed to {$next}.\n\n" . $note);
            } else {
                add_request_comment((int)$requestId, $techId, "Status changed to {$next}.");
            }

            header('Location: ' . base_url('/technician/view.php?id=' . (int)$requestId));
            exit;
        }
    } elseif ($action === 'comment') {
        $comment = trim((string)($_POST['comment'] ?? ''));

        if ($comment === '') {
            $error = 'Comment cannot be empty.';
        } elseif (mb_strlen($comment) > 2000) {
            $error = 'Comment is too long.';
        } else {
            add_request_comment((int)$requestId, $techId, $comment);
            header('Location: ' . base_url('/technician/view.php?id=' . (int)$requestId));
            exit;
        }
    } else {
        $error = 'Invalid action.';
    }

    // Refresh request after a failed POST (or for up-to-date UI).
    $request = get_request_by_id((int)$requestId) ?: $request;
}

$comments = list_request_comments((int)$requestId);
$nextStatuses = allowed_next_statuses((string)$request['status']);

render_header('Request #' . (int)$requestId);
?>
<link rel="stylesheet" href="<?php echo h(base_url('/assets/css/technician_view.css')); ?>" />

<?php
    $status = (string)$request['status'];
    $statusLabel = $status;
    $statusClass = 'text-bg-secondary';
    if ($status === 'new') {
        $statusLabel = 'pending';
        $statusClass = 'text-bg-warning';
    } elseif ($status === 'in_progress') {
        $statusLabel = 'in progress';
        $statusClass = 'text-bg-primary';
    } elseif ($status === 'resolved') {
        $statusLabel = 'completed';
        $statusClass = 'text-bg-success';
    }

    $priority = (string)$request['priority'];
    $priorityClass = 'text-bg-secondary';
    if ($priority === 'medium') {
        $priorityClass = 'text-bg-warning';
    } elseif ($priority === 'high') {
        $priorityClass = 'text-bg-danger';
    }
?>

<div class="d-flex align-items-start justify-content-between mb-3">
    <div>
        <h1 class="h4 m-0">Request #<?php echo (int)$request['id']; ?></h1>
        <div class="text-muted small mb-2"><?php echo h($request['title']); ?></div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge <?php echo h($statusClass); ?>"><?php echo h($statusLabel); ?></span>
            <span class="badge <?php echo h($priorityClass); ?>"><?php echo h($priority); ?></span>
            <?php if ($request['category_name']): ?>
                <span class="badge text-bg-light border"><?php echo h($request['category_name']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <a class="btn btn-outline-secondary" href="<?php echo h(base_url('/technician/index.php')); ?>">Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 mb-3">Details</h2>
                <dl class="cm-meta mb-0">
                    <div class="row">
                        <dt class="col-5">Status</dt>
                        <dd class="col-7"><span class="badge <?php echo h($statusClass); ?>"><?php echo h($statusLabel); ?></span></dd>
                    </div>
                    <div class="row">
                        <dt class="col-5">Priority</dt>
                        <dd class="col-7"><span class="badge <?php echo h($priorityClass); ?>"><?php echo h($priority); ?></span></dd>
                    </div>
                    <div class="row">
                        <dt class="col-5">Category</dt>
                        <dd class="col-7"><?php echo $request['category_name'] ? h($request['category_name']) : '—'; ?></dd>
                    </div>
                    <div class="row">
                        <dt class="col-5">Assigned</dt>
                        <dd class="col-7"><?php echo $request['assigned_to_name'] ? h($request['assigned_to_name']) : '<span class="text-muted">Unassigned</span>'; ?></dd>
                    </div>
                    <div class="row">
                        <dt class="col-5">Created By</dt>
                        <dd class="col-7"><?php echo h($request['created_by_name']); ?></dd>
                    </div>
                    <div class="row">
                        <dt class="col-5">Location</dt>
                        <dd class="col-7"><?php echo h($request['location']); ?></dd>
                    </div>
                    <div class="row">
                        <dt class="col-5">Created</dt>
                        <dd class="col-7"><?php echo h($request['created_at']); ?></dd>
                    </div>
                    <div class="row mb-0">
                        <dt class="col-5">Updated</dt>
                        <dd class="col-7 mb-0"><?php echo h($request['updated_at']); ?></dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h6">Description</h2>
                <p class="mb-0" style="white-space: pre-wrap;"><?php echo h($request['description']); ?></p>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h2 class="h6">Update Status</h2>

                <?php if (!$nextStatuses): ?>
                    <p class="text-muted mb-0">No further status changes allowed.</p>
                <?php else: ?>
                    <form method="post" action="<?php echo h(base_url('/technician/view.php?id=' . (int)$requestId)); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="status" />

                        <div class="mb-2">
                            <label class="form-label">Next Status</label>
                            <select name="status" class="form-select" required>
                                <?php foreach ($nextStatuses as $s): ?>
                                    <option value="<?php echo h($s); ?>"><?php echo h($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Note (optional)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="What did you do / what’s next?"></textarea>
                        </div>

                        <button class="btn btn-primary" type="submit">Save</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h2 class="h6 mb-0">Notes / Updates</h2>
                        <div class="text-muted small">Add internal notes and updates for tracking work</div>
                    </div>
                </div>

                <form method="post" action="<?php echo h(base_url('/technician/view.php?id=' . (int)$requestId)); ?>" class="mb-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="comment" />

                    <div class="mb-2">
                        <label class="form-label small text-muted">Add a note</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Add an internal note..." required></textarea>
                    </div>
                    <button class="btn btn-outline-primary" type="submit">Add Note</button>
                </form>

                <?php if (!$comments): ?>
                    <div class="alert alert-light border mb-0" role="alert">
                        <div class="fw-semibold">No notes yet</div>
                        <div class="text-muted small">Add the first update after you start working on this request.</div>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($comments as $c): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong><?php echo h($c['user_name']); ?></strong>
                                    <span class="text-muted small"><?php echo h($c['created_at']); ?></span>
                                </div>
                                <div class="small text-muted mb-1"><?php echo h($c['user_role']); ?></div>
                                <div style="white-space: pre-wrap;"><?php echo h($c['comment']); ?></div>
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
