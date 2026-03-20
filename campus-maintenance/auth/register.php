<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/user.php';
require_once __DIR__ . '/../includes/layout.php'; // for h()

if (is_logged_in()) {
    redirect_to_dashboard();
}

$error = '';
$username = '';
$email = '';
$regBgUrl = base_url('Images/hero-bg.jpg');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $role = 'student';
    $allowPrivileged = false;

    // Validate input.
    if ($error) {
        // keep error
    } elseif ($username === '' || $email === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } elseif (cm_strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (cm_strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        // Prevent duplicate accounts.
        $existing = find_user_by_email($email);
        if ($existing) {
            $error = 'An account with that email already exists.';
        } elseif (find_user_by_username($username)) {
            $error = 'That username is already taken.';
        } else {
            $newUserId = create_user($username, $email, $password, $role, $allowPrivileged);
            if ($newUserId <= 0) {
                $error = 'Registration failed. Please try again.';
            } else {
                // After successful registration, continue with login.
                header('Location: ' . base_url('/auth/login.php?registered=1'));
                exit;
            }
        }
    }
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - Campus Maintenance</title>
    <link rel="stylesheet" href="<?php echo h(base_url('/assets/vendor/bootstrap/bootstrap.min.css')); ?>" />
    <link rel="stylesheet" href="<?php echo h(base_url('/assets/css/register.css')); ?>" />
    <style>
        :root {
            --cm-reg-bg: url('<?php echo htmlspecialchars($regBgUrl, ENT_QUOTES); ?>');
        }
    </style>
</head>
<body class="cm-auth-hero">
    <div class="cm-auth-wrap">
        <div class="cm-auth-shell">
            <div class="cm-auth-grid">
                <!-- Side content cleared per request (layout preserved) -->
                <div class="cm-auth-side cm-anim" style="animation-delay: 80ms;"></div>

                <!-- Form card (right on desktop) -->
                <div class="cm-auth-card cm-anim" style="animation-delay: 120ms;">
                    <?php if ($error): ?>
                        <div class="cm-auth-alert" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form class="cm-auth-form" method="post" action="">
                    <!--
                        NOTE:
                        - Backend expects: username, email, password
                        - All self-signups are created as Student accounts.
                        - We validate "Confirm Password" on the client so functionality remains intact.
                    -->
                    <div class="cm-formGrid">
                        <div>
                            <label class="cm-auth-label" for="reg_username">Full Name</label>
                            <div class="cm-auth-inputRow">
                                <svg class="cm-auth-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5V21h16v-2.5C20 16 16.42 14 12 14Z"/>
                                </svg>
                                <input id="reg_username" type="text" name="username" class="cm-auth-input" required placeholder="Enter your full name" value="<?php echo htmlspecialchars($username); ?>" />
                            </div>
                        </div>

                        <div>
                            <label class="cm-auth-label" for="reg_email">Email</label>
                            <div class="cm-auth-inputRow">
                                <svg class="cm-auth-icon" height="20" viewBox="0 0 32 32" width="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <g>
                                        <path d="m30.853 13.87a15 15 0 0 0 -29.729 4.082 15.1 15.1 0 0 0 12.876 12.918 15.6 15.6 0 0 0 2.016.13 14.85 14.85 0 0 0 7.715-2.145 1 1 0 1 0 -1.031-1.711 13.007 13.007 0 1 1 5.458-6.529 2.149 2.149 0 0 1 -4.158-.759v-10.856a1 1 0 0 0 -2 0v1.726a8 8 0 1 0 .2 10.325 4.135 4.135 0 0 0 7.83.274 15.2 15.2 0 0 0 .823-7.455zm-14.853 8.13a6 6 0 1 1 6-6 6.006 6.006 0 0 1 -6 6z"/>
                                    </g>
                                </svg>
                                <input id="reg_email" type="email" name="email" class="cm-auth-input" required placeholder="Enter your Email" value="<?php echo htmlspecialchars($email); ?>" />
                            </div>
                        </div>

                        <div>
                            <label class="cm-auth-label" for="reg_password">Password</label>
                            <div class="cm-auth-inputRow">
                                <svg class="cm-auth-icon" height="20" viewBox="-64 0 512 512" width="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="m336 512h-288c-26.453125 0-48-21.523438-48-48v-224c0-26.476562 21.546875-48 48-48h288c26.453125 0 48 21.523438 48 48v224c0 26.476562-21.546875 48-48 48zm-288-288c-8.8125 0-16 7.167969-16 16v224c0 8.832031 7.1875 16 16 16h288c8.8125 0 16-7.167969 16-16v-224c0-8.832031-7.1875-16-16-16zm0 0"/>
                                    <path d="m304 224c-8.832031 0-16-7.167969-16-16v-80c0-52.929688-43.070312-96-96-96s-96 43.070312-96 96v80c0 8.832031-7.167969 16-16 16s-16-7.167969-16-16v-80c0-70.59375 57.40625-128 128-128s128 57.40625 128 128v80c0 8.832031-7.167969 16-16 16zm0 0"/>
                                </svg>
                                <input id="reg_password" type="password" name="password" class="cm-auth-input" required placeholder="Create a Password" />
                            </div>
                            <div class="cm-auth-help">At least 8 characters.</div>
                        </div>

                        <div>
                            <!-- Confirm Password (UI-only validation; backend remains unchanged) -->
                            <label class="cm-auth-label" for="reg_password_confirm">Confirm Password</label>
                            <div class="cm-auth-inputRow">
                                <svg class="cm-auth-icon" height="20" viewBox="-64 0 512 512" width="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="m336 512h-288c-26.453125 0-48-21.523438-48-48v-224c0-26.476562 21.546875-48 48-48h288c26.453125 0 48 21.523438 48 48v224c0 26.476562-21.546875 48-48 48zm-288-288c-8.8125 0-16 7.167969-16 16v224c0 8.832031 7.1875 16 16 16h288c8.8125 0 16-7.167969 16-16v-224c0-8.832031-7.1875-16-16-16zm0 0"/>
                                    <path d="m304 224c-8.832031 0-16-7.167969-16-16v-80c0-52.929688-43.070312-96-96-96s-96 43.070312-96 96v80c0 8.832031-7.167969 16-16 16s-16-7.167969-16-16v-80c0-70.59375 57.40625-128 128-128s128 57.40625 128 128v80c0 8.832031-7.167969 16-16 16zm0 0"/>
                                </svg>
                                <input id="reg_password_confirm" type="password" name="confirm_password" class="cm-auth-input" required placeholder="Re-enter your Password" />
                            </div>
                            <div class="cm-auth-help" id="pwHelp">Passwords must match.</div>
                        </div>
                    </div>

                    <!-- Submit button (keeps POST submission unchanged) -->
                    <button type="submit" class="cm-auth-submit">Register</button>

                    <p class="cm-auth-text">Already have an account? <a class="cm-auth-link" href="<?php echo h(base_url('/login.php')); ?>">Sign in</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo h(base_url('/assets/vendor/bootstrap/bootstrap.bundle.min.js')); ?>"></script>
    <script src="<?php echo h(base_url('/assets/js/app.js')); ?>"></script>
    <script>
        (function () {
            /* Client-side confirm-password validation (does not change backend behavior) */
            var pw = document.getElementById('reg_password');
            var pw2 = document.getElementById('reg_password_confirm');
            var help = document.getElementById('pwHelp');
            if (!pw || !pw2) return;

            function syncPasswords() {
                var a = pw.value || '';
                var b = pw2.value || '';
                var ok = (a !== '' && b !== '' && a === b);

                // Use built-in HTML validation UI
                pw2.setCustomValidity(ok || b === '' ? '' : 'Passwords do not match.');

                if (help) {
                    help.style.color = ok ? '#9be7b5' : '#aaa';
                }
            }

            pw.addEventListener('input', syncPasswords);
            pw2.addEventListener('input', syncPasswords);
            syncPasswords();
        })();

    </script>
</body>
</html>
