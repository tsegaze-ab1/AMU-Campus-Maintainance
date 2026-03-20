<?php
// includes/layout.php
// Minimal layout helpers (Bootstrap 5) used across pages.

require_once __DIR__ . '/auth.php';

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cm_strlen(string $value): int
{
    return function_exists('mb_strlen') ? (int)mb_strlen($value) : (int)strlen($value);
}

function render_header(string $title): void
{
    $user = current_user();
    $role = $user['role'] ?? '';
    $hideNavUserLabel = !empty($GLOBALS['CM_HIDE_NAV_USER_LABEL']);
    $hideHeaderNavLinks = !empty($GLOBALS['CM_HIDE_HEADER_NAV_LINKS']);

    ?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo h($title); ?></title>
    <link rel="stylesheet" href="<?php echo h(base_url('/assets/vendor/bootstrap/bootstrap.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo h(base_url('/assets/css/style.css')); ?>" />
    <style>
        /* Lightweight dark-mode support for dashboards */
        body.cm-dark {
            background-color: #0b0f16 !important;
            color: #e9ecf5;
        }
        body.cm-dark .navbar { background-color: #0f1725 !important; }
        body.cm-dark .navbar .nav-link, body.cm-dark .navbar .navbar-brand { color: #e9ecf5 !important; }
        body.cm-dark .navbar .navbar-text { color: rgba(233, 236, 245, 0.72) !important; }
        body.cm-dark .card { background-color: #111827; border-color: rgba(255,255,255,0.08); color: #e9ecf5; }
        body.cm-dark .list-group-item { background-color: #0f1725; color: #e9ecf5; border-color: rgba(255,255,255,0.08); }
        body.cm-dark .offcanvas { background-color: #0f1725; color: #e9ecf5; }
        body.cm-dark .table { color: #e9ecf5; }
        body.cm-dark .text-muted { color: rgba(233,236,245,0.72) !important; }
        body.cm-dark .form-control, body.cm-dark .form-select { background-color: #0f1725; color: #e9ecf5; border-color: rgba(255,255,255,0.14); }
        body.cm-dark .btn-outline-primary { color: #9cc7ff; border-color: #2d79f3; }
        .cm-theme-toggle { border-radius: 999px; }
    </style>
</head>
<body class="bg-light" id="cmBody">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo h(base_url('/')); ?>">Campus Maintenance</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <?php if (!$hideHeaderNavLinks): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if ($role === 'student'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/student/dashboard.php')); ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/student/index.php')); ?>">My Requests</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/student/create.php')); ?>">Create Request</a></li>
                    <?php elseif ($role === 'technician'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/technician/dashboard.php')); ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/technician/index.php')); ?>">Assigned To Me</a></li>
                    <?php elseif ($role === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/admin/dashboard.php')); ?>">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/admin/requests.php')); ?>">Requests</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/admin/categories.php')); ?>">Categories</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/admin/user_management.php')); ?>">Users</a></li>
                    <?php elseif ($role === 'admin2'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/admin2/dashboard.php')); ?>">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item me-2">
                    <button class="btn btn-sm btn-outline-light cm-theme-toggle" type="button" id="cmThemeToggle" aria-label="Toggle dark mode">
                        Theme
                    </button>
                </li>
                <?php if ($user): ?>
                    <?php if (!$hideNavUserLabel): ?>
                        <li class="nav-item"><span class="navbar-text text-white-50 me-3"><?php echo h($user['email'] ?? ''); ?></span></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/logout.php')); ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo h(base_url('/login.php')); ?>">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-4">
<?php
}

function render_footer(): void
{
    ?>
</div>
<script src="<?php echo h(base_url('/assets/vendor/bootstrap/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo h(base_url('/assets/js/app.js')); ?>"></script>
<script>
    (function () {
        var body = document.getElementById('cmBody');
        var toggle = document.getElementById('cmThemeToggle');
        if (!body || !toggle) return;

        var storageKey = 'cm-theme';
        var preferred = localStorage.getItem(storageKey) || 'light';

        function applyTheme(mode) {
            if (mode === 'dark') {
                body.classList.add('cm-dark');
                document.documentElement.setAttribute('data-bs-theme', 'dark');
                toggle.textContent = 'Light';
                toggle.setAttribute('aria-label', 'Switch to light mode');
            } else {
                body.classList.remove('cm-dark');
                document.documentElement.setAttribute('data-bs-theme', 'light');
                toggle.textContent = 'Dark';
                toggle.setAttribute('aria-label', 'Switch to dark mode');
            }
        }

        applyTheme(preferred);

        toggle.addEventListener('click', function () {
            preferred = (preferred === 'dark') ? 'light' : 'dark';
            localStorage.setItem(storageKey, preferred);
            applyTheme(preferred);
        });
    })();
</script>
</body>
</html>
<?php
}
