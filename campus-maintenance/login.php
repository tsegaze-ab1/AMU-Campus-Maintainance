<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/user.php';
require_once __DIR__ . '/includes/layout.php'; // for h() helper

// If already logged in, send user to their role dashboard.
if (is_logged_in()) {
    redirect_to_dashboard();
}

$error = '';
$email = '';
$selectedRole = 'student';
$logoUrl = BASE_URL . '/Images/Amu%20logo.jpg';
$heroUrl = BASE_URL . '/Images/hero-bg.jpg';
$ctaUrl = BASE_URL . '/Images/cta-bg.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $selectedRole = (string)($_POST['role'] ?? 'student');
    if (!in_array($selectedRole, ['student', 'technician', 'admin'], true)) {
        $selectedRole = 'student';
    }

    // Basic input validation.
    if ($email === '' || $password === '') {
        $error = 'Please enter email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Fetch user via prepared statement.
        $user = find_user_by_email($email);

        // Validate hashed password.
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            $error = 'Invalid email or password.';
        } else {
            $actualRole = (string)($user['role'] ?? '');
            if (in_array($actualRole, ['student', 'technician', 'admin'], true) && $selectedRole !== $actualRole) {
                $error = 'Selected role does not match this account. Please choose: ' . ucfirst($actualRole) . '.';
            } else {
            // Mitigate session fixation.
            session_regenerate_id(true);

            // Store safe fields in session.
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'username' => (string)$user['username'],
                'email' => (string)$user['email'],
                'role' => (string)$user['role'],
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
    <!-- Bootstrap 5 (CDN) -->
        <link rel="stylesheet" href="<?php echo h(base_url('/assets/css/login.css')); ?>" />
        <style>
            :root {
                --cm-hero-img: url('<?php echo htmlspecialchars($heroUrl, ENT_QUOTES); ?>');
                --cm-cta-img: url('<?php echo htmlspecialchars($ctaUrl, ENT_QUOTES); ?>');
            }
            .cm-auth-text {
                text-align: center;
                color: #f1f1f1;
                font-size: 14px;
                margin: 8px 0;
            }

            .cm-auth-link {
                color: #2d79f3;
                font-weight: 600;
                text-decoration: none;
            }

            .cm-auth-link:hover {
                text-decoration: underline;
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

            .cm-auth-icon {
                width: 20px;
                height: 20px;
                fill: #cfcfcf;
                flex: 0 0 auto;
            }
        </style>
    </style>
</head>
<body>
    <div class="cm-heroLayer" aria-hidden="true"></div>
    <div class="cm-brandMark">
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Arbaminch University logo" />
        <span class="cm-brandText" aria-label="Arbaminch University">Arbaminch University</span>
    </div>
    <div class="cm-auth-wrap">
        <div class="cm-auth-shell">
            <div class="cm-auth-grid">
                <div class="cm-auth-side">
                    <div class="cm-auth-heroCard" aria-hidden="true">
                        <span class="cm-heroBadge">Always on-duty support</span>
                        <div class="cm-pillRow">
                            <span class="cm-pill">Fast requests</span>
                            <span class="cm-pill">Real-time updates</span>
                            <span class="cm-pill">Campus-wide coverage</span>
                        </div>
                    </div>
                    <h2 class="cm-auth-sideTitle">Campus Maintenance System</h2>
                    <p class="cm-auth-sideLead">Welcome to the Campus Maintenance System! Our platform helps students submit maintenance requests easily, allows technicians to track and solve issues efficiently, and keeps everyone updated in real-time.</p>
                    <p class="cm-auth-sideBody">Report issues in minutes, follow progress transparently, and collaborate with the right team without losing time. The experience is designed to stay lightweight and responsive across devices while keeping your existing login flow intact.</p>
                </div>

                <div class="cm-auth-card">
                    <h1 class="cm-auth-title">Sign in</h1>
                    <p class="cm-auth-subtitle">Campus Maintenance Management System</p>

                    <?php if ($error): ?>
                        <div class="cm-auth-alert" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form class="cm-auth-form" method="post" action="<?php echo htmlspecialchars(BASE_URL . '/login.php'); ?>">
                        <label class="cm-auth-label" for="login_email">Email</label>
                        <div class="cm-auth-inputRow">
                            <svg class="cm-auth-icon" height="20" viewBox="0 0 32 32" width="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <g>
                                    <path d="m30.853 13.87a15 15 0 0 0 -29.729 4.082 15.1 15 0 0 0 12.876 12.918 15.6 15.6 0 0 0 2.016.13 14.85 14.85 0 0 0 7.715-2.145 1 1 0 1 0 -1.031-1.711 13.007 13.007 0 1 1 5.458-6.529 2.149 2.149 0 0 1 -4.158 -.759v-10.856a1 1 0 0 0 -2 0v1.726a8 8 0 1 0 .2 10.325 4.135 4.135 0 0 0 7.83.274 15.2 15.2 0 0 0 .823-7.455zm-14.853 8.13a6 6 0 1 1 6-6 6.006 6.006 0 0 1 -6 6z"/>
                                </g>
                            </svg>
                            <input id="login_email" type="email" name="email" class="cm-auth-input" required placeholder="Enter your Email" value="<?php echo htmlspecialchars($email); ?>" />
                        </div>

                        <label class="cm-auth-label" for="login_role">Account Type</label>
                        <div class="cm-auth-inputRow">
                            <svg class="cm-auth-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M12 2a5 5 0 0 0-5 5v1H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 6V7a3 3 0 1 1 6 0v1H9Z"/>
                            </svg>
                            <select id="login_role" name="role" class="cm-auth-select" aria-label="Choose account type">
                                <option value="student" <?php echo $selectedRole === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="technician" <?php echo $selectedRole === 'technician' ? 'selected' : ''; ?>>Technician</option>
                                <option value="admin" <?php echo $selectedRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
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

                        <p class="cm-auth-text">Don’t have an account? <a class="cm-auth-link" href="<?php echo htmlspecialchars(BASE_URL . '/register.php'); ?>">Sign up</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo h(base_url('/assets/js/app.js')); ?>"></script>
</body>
</html>
