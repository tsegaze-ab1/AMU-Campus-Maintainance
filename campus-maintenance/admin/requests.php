<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/requests.php';

require_role(['admin']);

$counts = request_counts_for_admin();
$requests = list_all_requests();

render_header('Admin - Requests');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 m-0">All Requests</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total</div><div class="h4 mb-0"><?php echo (int)$counts['total']; ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">New</div><div class="h4 mb-0"><?php echo (int)$counts['new']; ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">In Progress</div><div class="h4 mb-0"><?php echo (int)$counts['in_progress']; ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Resolved</div><div class="h4 mb-0"><?php echo (int)$counts['resolved']; ?></div></div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (!$requests): ?>
            <p class="mb-0">No requests found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Assigned To</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><?php echo (int)$r['id']; ?></td>
                                <td><?php echo h($r['title']); ?></td>
                                <td><?php echo $r['category_name'] ? h($r['category_name']) : '—'; ?></td>
                                <td><?php echo h($r['location']); ?></td>
                                <td><span class="badge text-bg-secondary"><?php echo h($r['priority']); ?></span></td>
                                <td>
                                    <?php
                                        $status = (string)$r['status'];
                                        $class = 'text-bg-secondary';
                                        if ($status === 'new') {
                                            $class = 'text-bg-warning';
                                        }
                                        if ($status === 'in_progress') {
                                            $class = 'text-bg-primary';
                                        }
                                        if ($status === 'resolved') {
                                            $class = 'text-bg-success';
                                        }
                                    ?>
                                    <span class="badge <?php echo h($class); ?>"><?php echo h($status); ?></span>
                                </td>
                                <td><?php echo h($r['created_by_name']); ?></td>
                                <td><?php echo $r['assigned_to_name'] ? h($r['assigned_to_name']) : '<span class="text-muted">Unassigned</span>'; ?></td>
                                <td><?php echo h($r['updated_at']); ?></td>
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
<?php
render_footer();
