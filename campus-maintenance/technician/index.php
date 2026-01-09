<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/requests.php';

require_role(['technician', 'staff']);

$user = current_user();
$techId = (int)($user['id'] ?? 0);

$counts = request_counts_for_technician($techId);
$requests = list_requests_assigned_to($techId);

$urlDashboard = base_url('/technician_dashboard.php');
$urlAssigned = base_url('/technician/index.php');

render_header('Assigned To Me');
?>
<link rel="stylesheet" href="<?php echo h(base_url('/assets/css/technician_list.css')); ?>" />

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h1 class="h4 m-0">Assigned To Me</h1>
        <div class="text-muted small">Requests currently assigned to you</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
        <a class="btn btn-outline-primary" href="<?php echo h($urlAssigned); ?>">Refresh</a>
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
                <div class="fw-semibold">No assigned requests</div>
                <div class="text-muted small">When a request is assigned to you, it will appear here.</div>
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
                            <th class="d-none d-lg-table-cell">Created By</th>
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
                                <td class="d-none d-lg-table-cell"><?php echo h($r['created_by_name']); ?></td>
                                <td class="d-none d-xl-table-cell text-muted small"><?php echo h($r['updated_at']); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo h(base_url('/technician/view.php?id=' . (int)$r['id'])); ?>">View</a>
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
