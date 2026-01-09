<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/requests.php';

require_role(['student']);

$user = current_user();
$studentId = (int)($user['id'] ?? 0);
$username = $user['username'] ?? $user['email'] ?? 'User';
$counts = request_counts_for_student($studentId);

// Used only for display on the dashboard (no query changes; reuses existing helper).
$requests = list_requests_for_student($studentId);

$urlDashboard = base_url('/student_dashboard.php');
$urlMyRequests = base_url('/student/index.php');
$urlCreateRequest = base_url('/student/create.php');
$urlLogout = base_url('/logout.php');

render_header('Student Dashboard');
?>
<link rel="stylesheet" href="<?php echo h(base_url('/assets/css/student_dashboard.css')); ?>" />

<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#studentSidebar" aria-controls="studentSidebar">
            Menu
        </button>
        <div>
            <h1 class="h4 m-0 cm-pageTitle">Student Dashboard</h1>
            <div class="text-muted small">Welcome back, <strong><?php echo h($username); ?></strong>.</div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?php echo h($urlMyRequests); ?>">My Requests</a>
        <a class="btn btn-primary" href="<?php echo h($urlCreateRequest); ?>">Create Request</a>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="studentSidebar" aria-labelledby="studentSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="studentSidebarLabel">Student Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group">
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlMyRequests); ?>">My Requests</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlCreateRequest); ?>">Create Request</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlLogout); ?>">Logout</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-3 d-none d-lg-block">
        <div class="card shadow-sm cm-sidebar">
            <div class="card-body">
                <div class="fw-semibold mb-2">Navigation</div>
                <nav class="nav nav-pills flex-column gap-1">
                    <a class="nav-link active" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
                    <a class="nav-link" href="<?php echo h($urlMyRequests); ?>">My Requests</a>
                    <a class="nav-link" href="<?php echo h($urlCreateRequest); ?>">Create Request</a>
                    <a class="nav-link" href="<?php echo h($urlLogout); ?>">Logout</a>
                </nav>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <div class="fw-semibold mb-1">Quick Tips</div>
                <div class="text-muted small">Use “Create Request” to report an issue. You can add updates on each request page.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-primary); --cm-accent-rgb: var(--bs-primary-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Total Requests</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['total']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true">
                            <i class="bi bi-collection" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-warning); --cm-accent-rgb: var(--bs-warning-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Pending</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['new']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true">
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-info); --cm-accent-rgb: var(--bs-info-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">In Progress</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['in_progress']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-success); --cm-accent-rgb: var(--bs-success-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Completed</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['resolved']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
            $stPending = (int)$counts['new'];
            $stInProgress = (int)$counts['in_progress'];
            $stCompleted = (int)$counts['resolved'];
            $stTotal = max(0, (int)$counts['total']);
            $stDonePct = $stTotal > 0 ? (int)round(($stCompleted / $stTotal) * 100) : 0;
        ?>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h2 class="h6 mb-0">Request Status</h2>
                                <div class="text-muted small">Your current request distribution</div>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo h($urlMyRequests); ?>">View</a>
                        </div>

                        <div class="cm-chartWrap">
                            <canvas id="studentStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h2 class="h6 mb-0">Completion</h2>
                                <div class="text-muted small">Completed <?php echo $stCompleted; ?> of <?php echo $stTotal; ?> requests</div>
                            </div>
                            <span class="badge text-bg-success"><?php echo $stDonePct; ?>%</span>
                        </div>

                        <div class="progress" role="progressbar" aria-label="Completion" aria-valuenow="<?php echo $stDonePct; ?>" aria-valuemin="0" aria-valuemax="100" style="height: 12px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $stDonePct; ?>%"></div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3 cm-soft">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">Pending</div>
                                        <span class="badge text-bg-warning"><?php echo $stPending; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3" style="background: rgba(var(--bs-info-rgb), .08); border: 1px solid rgba(0,0,0,.08);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">In progress</div>
                                        <span class="badge text-bg-primary"><?php echo $stInProgress; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3" style="background: rgba(var(--bs-success-rgb), .08); border: 1px solid rgba(0,0,0,.08);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">Completed</div>
                                        <span class="badge text-bg-success"><?php echo $stCompleted; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h2 class="h6 mb-0">My Requests</h2>
                        <div class="text-muted small">Recent maintenance requests and their latest status</div>
                    </div>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo h($urlMyRequests); ?>">View all</a>
                </div>

                <?php if (!$requests): ?>
                    <div class="alert alert-light border mb-0" role="alert">
                        <div class="fw-semibold">No requests yet</div>
                        <div class="text-muted small">Create your first maintenance request to get started.</div>
                        <div class="mt-2">
                            <a class="btn btn-sm btn-primary" href="<?php echo h($urlCreateRequest); ?>">Create Request</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php $recent = array_slice($requests, 0, 8); ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">ID</th>
                                    <th>Title</th>
                                    <th class="d-none d-md-table-cell">Location</th>
                                    <th style="width: 120px;">Priority</th>
                                    <th style="width: 140px;">Status</th>
                                    <th class="d-none d-lg-table-cell">Assigned</th>
                                    <th class="d-none d-xl-table-cell">Updated</th>
                                    <th style="width: 90px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $r): ?>
                                    <tr>
                                        <td class="text-muted">#<?php echo (int)$r['id']; ?></td>
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
                                                $sClass = 'text-bg-secondary';
                                                if ($status === 'new') {
                                                    $label = 'pending';
                                                    $sClass = 'text-bg-warning';
                                                } elseif ($status === 'in_progress') {
                                                    $label = 'in progress';
                                                    $sClass = 'text-bg-primary';
                                                } elseif ($status === 'resolved') {
                                                    $label = 'completed';
                                                    $sClass = 'text-bg-success';
                                                }
                                            ?>
                                            <span class="badge <?php echo h($sClass); ?>"><?php echo h($label); ?></span>
                                        </td>
                                        <td class="d-none d-lg-table-cell">
                                            <?php echo $r['assigned_name'] ? h($r['assigned_name']) : '<span class="text-muted">Unassigned</span>'; ?>
                                        </td>
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
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    window.STUDENT_COUNTS = {
        pending: <?php echo (int)$counts['new']; ?>,
        inProgress: <?php echo (int)$counts['in_progress']; ?>,
        completed: <?php echo (int)$counts['resolved']; ?>
    };
</script>
<script src="<?php echo h(base_url('/assets/js/student_dashboard.js')); ?>"></script>
<?php
render_footer();
