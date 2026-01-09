<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/requests.php';

require_role(['student']);

$user = current_user();
$studentId = (int)($user['id'] ?? 0);

$counts = request_counts_for_student($studentId);
$requests = list_requests_for_student($studentId);

$urlDashboard = base_url('/student_dashboard.php');
$urlMyRequests = base_url('/student/index.php');
$urlCreateRequest = base_url('/student/create.php');

render_header('My Requests');
?>
<link rel="stylesheet" href="<?php echo h(base_url('/assets/css/student_list.css')); ?>" />

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h4 m-0">My Requests</h1>
        <div class="text-muted small">All maintenance requests you have submitted</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
        <a class="btn btn-primary" href="<?php echo h($urlCreateRequest); ?>">Create Request</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm cm-kpi">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Total</div>
                    <div class="h3 mb-0"><?php echo (int)$counts['total']; ?></div>
                </div>
                <div class="cm-kpi-icon" aria-hidden="true"><span class="fw-bold">Σ</span></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm cm-kpi">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Pending</div>
                    <div class="h3 mb-0"><?php echo (int)$counts['new']; ?></div>
                </div>
                <div class="cm-kpi-icon" aria-hidden="true"><span class="fw-bold">…</span></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm cm-kpi">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">In Progress</div>
                    <div class="h3 mb-0"><?php echo (int)$counts['in_progress']; ?></div>
                </div>
                <div class="cm-kpi-icon" aria-hidden="true"><span class="fw-bold">↻</span></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card shadow-sm cm-kpi">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Completed</div>
                    <div class="h3 mb-0"><?php echo (int)$counts['resolved']; ?></div>
                </div>
                <div class="cm-kpi-icon" aria-hidden="true"><span class="fw-bold">✓</span></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!$requests): ?>
            <div class="alert alert-light border mb-0" role="alert">
                <div class="fw-semibold">No requests yet</div>
                <div class="text-muted small">Create your first maintenance request to get started.</div>
                <div class="mt-2">
                    <a class="btn btn-sm btn-primary" href="<?php echo h($urlCreateRequest); ?>">Create Request</a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th class="d-none d-md-table-cell">Location</th>
                            <th style="width: 120px;">Priority</th>
                            <th style="width: 140px;">Status</th>
                            <th class="d-none d-lg-table-cell">Assigned</th>
                            <th class="d-none d-xl-table-cell">Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><?php echo (int)$r['id']; ?></td>
                                <td>
                                    <div class="fw-semibold cm-table-title text-truncate"><?php echo h($r['title']); ?></div>
                                    <div class="text-muted small d-md-none text-truncate"><?php echo h($r['location']); ?></div>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo h($r['location']); ?></td>
                                <td>
                                    <?php
                                        $priority = (string)$r['priority'];
                                        $pClass = 'text-bg-secondary';
                                        if ($priority === 'medium') {
                                            $pClass = 'text-bg-warning';
                                        } elseif ($priority === 'high') {
                                            $pClass = 'text-bg-danger';
                                        }
                                    ?>
                                    <span class="badge <?php echo h($pClass); ?>"><?php echo h($priority); ?></span>
                                </td>
                                <td>
                                    <?php
                                        $status = (string)$r['status'];
                                        $label = $status;
                                        $class = 'text-bg-secondary';
                                        if ($status === 'new') {
                                            $label = 'pending';
                                            $class = 'text-bg-warning';
                                        } elseif ($status === 'in_progress') {
                                            $label = 'in progress';
                                            $class = 'text-bg-primary';
                                        } elseif ($status === 'resolved') {
                                            $label = 'completed';
                                            $class = 'text-bg-success';
                                        }
                                    ?>
                                    <span class="badge <?php echo h($class); ?>"><?php echo h($label); ?></span>
                                </td>
                                <td class="d-none d-lg-table-cell"><?php echo $r['assigned_name'] ? h($r['assigned_name']) : '<span class="text-muted">Unassigned</span>'; ?></td>
                                <td class="d-none d-xl-table-cell text-muted small"><?php echo h($r['updated_at']); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo h(base_url('/student/view.php?id=' . (int)$r['id'])); ?>">View</a>
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
