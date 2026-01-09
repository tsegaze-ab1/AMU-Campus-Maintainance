<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/requests.php';
require_once __DIR__ . '/includes/admin.php';

require_role(['admin']);

$user = current_user();
$username = $user['username'] ?? $user['email'] ?? 'User';
$counts = request_counts_for_admin();

// Used only for display on the dashboard (no query changes; reuses existing helpers).
$users = list_users_basic();
$requests = list_all_requests();

$userCounts = [
    'student' => 0,
    'technician' => 0,
    'staff' => 0,
    'admin' => 0,
    'total' => 0,
];

foreach ($users as $u) {
    $role = (string)($u['role'] ?? '');
    if (isset($userCounts[$role])) {
        $userCounts[$role]++;
    }
    $userCounts['total']++;
}

$urlDashboard = base_url('/admin_dashboard.php');
$urlRequests = base_url('/admin/requests.php');
$urlCategories = base_url('/admin/categories.php');
$urlUsers = base_url('/admin/users.php');
$urlLogout = base_url('/logout.php');

render_header('Admin Dashboard');
?>
<link rel="stylesheet" href="<?php echo h(base_url('/assets/css/admin_dashboard.css')); ?>" />

<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
            Menu
        </button>
        <div>
            <h1 class="h4 m-0 cm-pageTitle">Admin Dashboard</h1>
            <div class="text-muted small">Welcome, <strong><?php echo h($username); ?></strong>. Overview of users and requests.</div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?php echo h($urlRequests); ?>">Manage Requests</a>
        <a class="btn btn-outline-primary" href="<?php echo h($urlUsers); ?>">Manage Users</a>
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
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlUsers); ?>">Users</a>
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
                    <a class="nav-link" href="<?php echo h($urlRequests); ?>">Requests</a>
                    <a class="nav-link" href="<?php echo h($urlCategories); ?>">Categories</a>
                    <a class="nav-link" href="<?php echo h($urlUsers); ?>">Users</a>
                    <a class="nav-link" href="<?php echo h($urlLogout); ?>">Logout</a>
                </nav>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <div class="fw-semibold mb-1">Quick Actions</div>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary" href="<?php echo h($urlRequests); ?>">Review Requests</a>
                    <a class="btn btn-outline-primary" href="<?php echo h($urlCategories); ?>">Edit Categories</a>
                    <a class="btn btn-outline-primary" href="<?php echo h($urlUsers); ?>">Manage Roles</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-primary); --cm-accent-rgb: var(--bs-primary-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Total Users</div>
                            <div class="h3 mb-0"><?php echo (int)$userCounts['total']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-people" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-info); --cm-accent-rgb: var(--bs-info-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Students</div>
                            <div class="h3 mb-0"><?php echo (int)$userCounts['student']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-mortarboard" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-warning); --cm-accent-rgb: var(--bs-warning-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Technicians</div>
                            <div class="h3 mb-0"><?php echo (int)$userCounts['technician']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-wrench-adjustable" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi" style="--cm-accent: var(--bs-success); --cm-accent-rgb: var(--bs-success-rgb);">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Total Requests</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['total']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-clipboard-data" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <?php
            $adPending = (int)$counts['new'];
            $adInProgress = (int)$counts['in_progress'];
            $adCompleted = (int)$counts['resolved'];
            $adTotal = max(0, (int)$counts['total']);
            $adDonePct = $adTotal > 0 ? (int)round(($adCompleted / $adTotal) * 100) : 0;
        ?>

        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h2 class="h6 mb-0">Request Status Overview</h2>
                                <div class="text-muted small">Pending vs in progress vs completed</div>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo h($urlRequests); ?>">View</a>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-12 col-md-6">
                                <div class="cm-chartWrap">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="d-grid gap-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Pending</span>
                                        <span class="badge text-bg-warning"><?php echo $adPending; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">In progress</span>
                                        <span class="badge text-bg-primary"><?php echo $adInProgress; ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Completed</span>
                                        <span class="badge text-bg-success"><?php echo $adCompleted; ?></span>
                                    </div>

                                    <hr class="my-2" />

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Overall completion</span>
                                        <span class="badge text-bg-success"><?php echo $adDonePct; ?>%</span>
                                    </div>
                                    <div class="progress" role="progressbar" aria-label="Overall completion" aria-valuenow="<?php echo $adDonePct; ?>" aria-valuemin="0" aria-valuemax="100" style="height: 12px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $adDonePct; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h2 class="h6 mb-0">User Roles</h2>
                                <div class="text-muted small">Distribution of accounts by role</div>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo h($urlUsers); ?>">View</a>
                        </div>

                        <div class="cm-chartWrap">
                            <canvas id="rolesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h2 class="h6 mb-0">Recent Requests</h2>
                        <div class="text-muted small">Latest updates across all requests</div>
                    </div>
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo h($urlRequests); ?>">View all</a>
                </div>

                <?php if (!$requests): ?>
                    <div class="alert alert-light border mb-0" role="alert">
                        <div class="fw-semibold">No requests found</div>
                        <div class="text-muted small">Requests will appear here once submitted.</div>
                    </div>
                <?php else: ?>
                    <?php $recent = array_slice($requests, 0, 8); ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 90px;">ID</th>
                                    <th>Title</th>
                                    <th class="d-none d-md-table-cell">Category</th>
                                    <th style="width: 140px;">Status</th>
                                    <th class="d-none d-lg-table-cell">Updated</th>
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
                                        <td class="d-none d-md-table-cell"><?php echo $r['category_name'] ? h($r['category_name']) : '—'; ?></td>
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
                                        <td class="d-none d-lg-table-cell text-muted small"><?php echo h($r['updated_at']); ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo h(base_url('/admin/view.php?id=' . (int)$r['id'])); ?>">View</a>
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
    window.ADMIN_COUNTS = {
        pending: <?php echo (int)$counts['new']; ?>,
        inProgress: <?php echo (int)$counts['in_progress']; ?>,
        completed: <?php echo (int)$counts['resolved']; ?>
    };
    window.ADMIN_ROLE_DATA = {
        students: <?php echo (int)$userCounts['student']; ?>,
        technicians: <?php echo (int)$userCounts['technician']; ?>,
        staff: <?php echo (int)$userCounts['staff']; ?>,
        admins: <?php echo (int)$userCounts['admin']; ?>
    };
</script>
<script src="<?php echo h(base_url('/assets/js/admin_dashboard.js')); ?>"></script>
<?php
render_footer();
