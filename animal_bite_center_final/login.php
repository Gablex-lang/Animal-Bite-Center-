<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
// ============================================================
//  login.php
//  Handles POST login requests from login.html via fetch()
//  Returns JSON — the HTML and CSS stay completely unchanged
// ============================================================

require_once __DIR__ . '/db.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

// ── Read JSON body sent by fetch() ───────────────────────────
$body     = file_get_contents('php://input');
$input    = json_decode($body, true);
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? trim($input['password']) : '';

// ── Basic validation ─────────────────────────────────────────
if (empty($username) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Username and password are required.']);
}

// ── Lookup user in database ───────────────────────────────────
try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        'SELECT id, username, password, role, first_name, middle_name, last_name
         FROM users
         WHERE username = ?
         LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

} catch (PDOException $e) {
    error_log('[LOGIN ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error. Please try again.'], 500);
}

// ── Verify password ───────────────────────────────────────────
if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid username or password.']);
}

// ── Build position from role + last name ─────────────────────
$position = getPosition($user['role'], $user['last_name']);

// ── Set session variables ─────────────────────────────────────
session_regenerate_id(true); // Prevent session fixation
$_SESSION['user_id']       = $user['id'];
$_SESSION['user_role']     = $user['role'];
$_SESSION['user_position'] = $position;
$_SESSION['user_name']     = $user['last_name'];
$_SESSION['user_fname']    = $user['first_name'];
$_SESSION['user_lname']    = $user['last_name'];

// ── Determine redirect target based on role ───────────────────
$redirectMap = [
    'SUPERADMIN' => 'superadmin_dashboard.html',
    'ADMIN'      => 'admin_dashboard.html',
    'STAFF'      => 'staff_dashboard.html',
];
$redirect = $redirectMap[$user['role']] ?? 'staff_dashboard.html';

// ── Return success response ───────────────────────────────────
jsonResponse([
    'success'  => true,
    'role'     => $user['role'],
    'position' => $position,
    'name'     => $user['last_name'],
    'redirect' => $redirect,
]);