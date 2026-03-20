<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_role(['admin2']);

$user = current_user();
$username = (string)($user['username'] ?? 'Admin2');

$GLOBALS['CM_HIDE_HEADER_NAV_LINKS'] = true;
render_header('Admin2 Dashboard');
?>
<div class="row g-3">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-2">Admin2 Dashboard</h1>
                <p class="text-muted mb-3">Welcome, <strong><?php echo h($username); ?></strong>.</p>
                <p class="mb-0">Your account is active with Admin2 role access.</p>
            </div>
        </div>
    </div>
</div>
<?php
render_footer();
