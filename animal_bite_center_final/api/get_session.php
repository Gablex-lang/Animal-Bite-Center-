<?php
// ============================================================
//  api/get_session.php
//  Called immediately when any dashboard HTML loads.
//  Validates the PHP session and returns user info to JS.
//  If session is invalid → returns 401 → JS redirects to login.
// ============================================================

require_once __DIR__ . '/../db.php';

// requireLogin() with no args just checks if logged in
$user = requireLogin();

jsonResponse([
    'success'  => true,
    'id'       => $user['id'],
    'role'     => $user['role'],
    'position' => $user['position'],
    'name'     => $user['name'],
]);