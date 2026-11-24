<?php
// Shared CMS session security and 10-minute server-side idle timeout.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$cmsIdleLimit = 600;
if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}
$cmsLastActivity = (int)($_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? time());
if (time() - $cmsLastActivity >= $cmsIdleLimit) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $cmsCookie = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $cmsCookie['path'], $cmsCookie['domain'], $cmsCookie['secure'], $cmsCookie['httponly']);
    }
    session_destroy();
    header('Location: /public/login.php?error=timeout');
    exit;
}
$_SESSION['last_activity'] = time();
?>
