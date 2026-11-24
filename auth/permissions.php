
<?php
// htdocs/auth/permissions.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Ensure a user is logged in.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login');
        exit;
    }
}

/**
 * Allow only administrators.
 */
function requireAdmin(): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('403 Forbidden: You do not have permission to access this page.');
    }
}
