<?php
// ============================================================
//  logout.php
//  Destroys the session and redirects to login.html
//  Called by logoutUser() in each dashboard's JS
// ============================================================

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session data
$_SESSION = [];

// Destroy session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Redirect to login page
header('Location: login.html');
exit;