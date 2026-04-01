


Upload target on InfinityFree: /htdocs/ (public_h

1) Upload all files and folders from this package to your InfinityFree htdocs directory.
2) In phpMyAdmin (InfinityFree), create/import the database using schema.sql.
3) Edit config/db.host.php with your InfinityFree MySQL details:
   - host
   - user
   - pass
   - name
4) Make sure ADMIN_REGISTRATION_KEY in includes/auth.php is changed to your own secure key.
5) Open your domain.
   - Public landing page: /index.php (frontend integrated)
   - Login page: /auth/login.php
   - Register page: /auth/register.php

Current behavior:
- Sign up button from frontend goes to /auth/register.php.
- After successful registration, user is redirected to /auth/login.php?registered=1.
- Logged-in users visiting /index.php are redirected to their role dashboard.
