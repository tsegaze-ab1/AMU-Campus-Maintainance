<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/requests.php';

require_role(['staff']);

$user = current_user();
$staffId = (int)($user['id'] ?? 0);
$username = $user['username'] ?? $user['email'] ?? 'User';
$counts = request_counts_for_technician($staffId);

$urlDashboard = base_url('/staff_dashboard.php');
$urlAssigned = base_url('/technician/index.php');
$urlLogout = base_url('/logout.php');

render_header('Staff Dashboard');
?>
<!-- Presentation CSS (moved from inline <style>) -->
<link rel="stylesheet" href="<?php echo h(base_url('/assets/css/staff_dashboard.css')); ?>" />

<div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#staffSidebar" aria-controls="staffSidebar">
            Menu
        </button>
        <div>
            <h1 class="h4 m-0 cm-pageTitle">Staff Dashboard</h1>
            <div class="text-muted small">Welcome, <strong><?php echo h($username); ?></strong>. Overview of your assigned jobs.</div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?php echo h($urlAssigned); ?>">Assigned To Me</a>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="staffSidebar" aria-labelledby="staffSidebarLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="staffSidebarLabel">Staff Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group">
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlAssigned); ?>">Assigned To Me</a>
            <a class="list-group-item list-group-item-action" href="<?php echo h($urlLogout); ?>">Logout</a>
        </div>
    </div>
</div>

<?php
    $sfPending = (int)$counts['new'];
    $sfInProgress = (int)$counts['in_progress'];
    $sfCompleted = (int)$counts['resolved'];
    $sfTotal = max(0, (int)$counts['total']);
    $sfDonePct = $sfTotal > 0 ? (int)round(($sfCompleted / $sfTotal) * 100) : 0;
?>

<div class="row g-3">
    <div class="col-12 col-lg-3 d-none d-lg-block">
        <div class="card shadow-sm cm-sidebar">
            <div class="card-body">
                <div class="fw-semibold mb-2">Navigation</div>
                <nav class="nav nav-pills flex-column gap-1">
                    <a class="nav-link active" href="<?php echo h($urlDashboard); ?>">Dashboard</a>
                    <a class="nav-link" href="<?php echo h($urlAssigned); ?>">Assigned To Me</a>
                    <a class="nav-link" href="<?php echo h($urlLogout); ?>">Logout</a>
                </nav>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <div class="fw-semibold mb-1">Tip</div>
                <div class="text-muted small">Open a job to update status and leave notes.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi cm-accent-primary">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Total Assigned</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['total']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-inboxes" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi cm-accent-warning">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Pending</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['new']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi cm-accent-info">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">In Progress</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['in_progress']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card shadow-sm cm-kpi cm-accent-success">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted small">Completed</div>
                            <div class="h3 mb-0"><?php echo (int)$counts['resolved']; ?></div>
                        </div>
                        <div class="cm-kpi-icon" aria-hidden="true"><i class="bi bi-check2-circle" aria-hidden="true"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h2 class="h6 mb-0">Work Status</h2>
                                <div class="text-muted small">Pending vs in progress vs completed</div>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo h($urlAssigned); ?>">View</a>
                        </div>

                        <div class="cm-chartWrap">
                            <canvas id="staffStatusChart"></canvas>
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
                                <div class="text-muted small">Completed <?php echo $sfCompleted; ?> of <?php echo $sfTotal; ?> assigned</div>
                            </div>
                            <span class="badge text-bg-success"><?php echo $sfDonePct; ?>%</span>
                        </div>

                        <div class="progress" role="progressbar" aria-label="Completion" aria-valuenow="<?php echo $sfDonePct; ?>" aria-valuemin="0" aria-valuemax="100" style="height: 12px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $sfDonePct; ?>%"></div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3 cm-soft-warning">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">Pending</div>
                                        <span class="badge text-bg-warning"><?php echo $sfPending; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3 cm-soft-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">In progress</div>
                                        <span class="badge text-bg-primary"><?php echo $sfInProgress; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-3 rounded-3 cm-soft-success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">Completed</div>
                                        <span class="badge text-bg-success"><?php echo $sfCompleted; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    // Dynamic data for external JS (minimal PHP only)
    window.STAFF_COUNTS = {
        pending: <?php echo (int)$counts['new']; ?>,
        inProgress: <?php echo (int)$counts['in_progress']; ?>,
        completed: <?php echo (int)$counts['resolved']; ?>
    };
</script>
<script src="<?php echo h(base_url('/assets/js/staff_dashboard.js')); ?>"></script>
<?php
render_footer();
