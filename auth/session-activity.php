<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
$last = (int)($_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? time());
if (time() - $last >= 600) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
    session_destroy(); http_response_code(401); echo json_encode(['ok'=>false]); exit;
}
$_SESSION['last_activity'] = time();
echo json_encode(['ok'=>true]);
