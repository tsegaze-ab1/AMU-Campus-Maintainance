<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/layout.php'; // for h() helper

// Bootstrap a default admin account for first deployment.
ensure_default_admin_user();

// If already logged in, send user to their role dashboard.
if (is_logged_in()) {
    redirect_to_dashboard();
}

$error = '';
$success = '';
$username = '';
$loginBgUrl = base_url('Images/hero-bg.jpg');

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success = 'Registration successful. Please sign in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    // Basic input validation.
    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        // Fetch user via prepared statement.
        $user = find_user_for_login($username);

        // Validate password (supports legacy stored passwords and upgrades on success).
        if (!$user || !verify_login_password($user, $password)) {
            $error = 'Invalid username or password.';
        } else {
            $actualRole = strtolower(trim((string)($user['role'] ?? '')));
            if (!in_array($actualRole, ['student', 'technician', 'admin', 'admin2'], true)) {
                $error = 'This account has an unsupported role. Contact administrator.';
            } else {
                // Mitigate session fixation.
                session_regenerate_id(true);

                // Store safe fields in session.
                $_SESSION['user'] = [
                    'id' => (int)$user['id'],
                    'username' => (string)$user['username'],
                    'email' => (string)($user['email'] ?? $user['username']),
                    'role' => $actualRole,
                ];

                redirect_to_dashboard();
            }
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - Campus Maintenance</title>
    <link rel="stylesheet" href="<?php echo h(base_url('/assets/css/login.css?v=20260319')); ?>" />
    <style>
        :root {
            --cm-login-bg: url('<?php echo htmlspecialchars($loginBgUrl, ENT_QUOTES); ?>');
        }

        .cm-auth-alert {
            border: 1px solid #5a1a1a;
            background: #2a1414;
            color: #ffd2d2;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .cm-auth-alert-success {
            border: 1px solid #1f5d3a;
            background: #10281b;
            color: #b8ffd5;
            border-radius: 12px;
            padding: 10px 12px;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .cm-auth-icon {
            width: 20px;
            height: 20px;
            fill: #cfcfcf;
            flex: 0 0 auto;
        }

        .cm-auth-register {
            margin-top: 12px;
            text-align: center;
            color: #bdbdbd;
            font-size: 14px;
        }

        .cm-auth-register a {
            color: #ffffff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="cm-auth-wrap">
        <div class="cm-auth-card">
            <h1 class="cm-auth-title">Sign in</h1>

            <?php if ($error): ?>
                <div class="cm-auth-alert" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="cm-auth-alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form class="cm-auth-form" method="post" action="">
                <label class="cm-auth-label" for="login_username">Username</label>
                <div class="cm-auth-inputRow">
                    <svg class="cm-auth-icon" height="20" viewBox="0 0 32 32" width="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <g>
                            <path d="m30.853 13.87a15 15 0 0 0 -29.729 4.082 15.1 15.1 0 0 0 12.876 12.918 15.6 15.6 0 0 0 2.016.13 14.85 14.85 0 0 0 7.715-2.145 1 1 0 1 0 -1.031-1.711 13.007 13.007 0 1 1 5.458-6.529 2.149 2.149 0 0 1 -4.158 -.759v-10.856a1 1 0 0 0 -2 0v1.726a8 8 0 1 0 .2 10.325 4.135 4.135 0 0 0 7.83.274 15.2 15.2 0 0 0 .823-7.455zm-14.853 8.13a6 6 0 1 1 6-6 6.006 6.006 0 0 1 -6 6z"/>
                        </g>
                    </svg>
                    <input id="login_username" type="text" name="username" class="cm-auth-input" required placeholder="Enter your Username" value="<?php echo htmlspecialchars($username); ?>" />
                </div>

                <label class="cm-auth-label" for="login_password">Password</label>
                <div class="cm-auth-inputRow">
                    <svg class="cm-auth-icon" height="20" viewBox="-64 0 512 512" width="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="m336 512h-288c-26.453125 0-48-21.523438-48-48v-224c0-26.476562 21.546875-48 48-48h288c26.453125 0 48 21.523438 48 48v224c0 26.476562-21.546875 48-48 48zm-288-288c-8.8125 0-16 7.167969-16 16v224c0 8.832031 7.1875 16 16 16h288c8.8125 0 16-7.167969 16-16v-224c0-8.832031-7.1875-16-16-16zm0 0"/>
                        <path d="m304 224c-8.832031 0-16-7.167969-16-16v-80c0-52.929688-43.070312-96-96-96s-96 43.070312-96 96v80c0 8.832031-7.167969 16-16 16s-16-7.167969-16-16v-80c0-70.59375 57.40625-128 128-128s128 57.40625 128 128v80c0 8.832031-7.167969 16-16 16zm0 0"/>
                    </svg>
                    <input id="login_password" type="password" name="password" class="cm-auth-input" required placeholder="Enter your Password" />
                </div>

                <button type="submit" class="cm-auth-submit">Sign In</button>
            </form>

            <p class="cm-auth-register">Don't have an account? <a href="<?php echo h(base_url('/register.php')); ?>">Register here</a></p>
        </div>
    </div>

    <script src="<?php echo h(base_url('/assets/vendor/bootstrap/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo h(base_url('/assets/js/app.js')); ?>"></script>
</body>
</html>
