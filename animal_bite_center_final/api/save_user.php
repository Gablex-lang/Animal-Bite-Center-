<?php
// ============================================================
//  api/save_user.php
//  Creates a new ADMIN or STAFF user — SUPERADMIN only
//  Passwords are hashed with bcrypt before storage
//  POST body: { fName, mName, lName, username, password, role }
// ============================================================

require_once __DIR__ . '/../db.php';

requireLogin('SUPERADMIN');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body  = file_get_contents('php://input');
$input = json_decode($body, true);

$fName    = strtoupper(trim($input['fName']    ?? ''));
$mName    = strtoupper(trim($input['mName']    ?? ''));
$lName    = strtoupper(trim($input['lName']    ?? ''));
$username = strtoupper(trim($input['username'] ?? ''));
$password = trim($input['password'] ?? '');
$role     = $input['role'] ?? '';

// Validate
if (empty($fName) || empty($lName) || empty($username) || empty($password) || empty($role)) {
    jsonResponse(['success' => false, 'message' => 'All fields are required.']);
}

if (!in_array($role, ['ADMIN', 'STAFF'], true)) {
    jsonResponse(['success' => false, 'message' => 'Invalid role. Only ADMIN or STAFF allowed.']);
}

if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
}

// Check username uniqueness
try {
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $check->execute([$username]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Username already exists.']);
    }

    // Hash password
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, middle_name, last_name, username, password, role)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $fName,
        $mName ?: null,
        $lName,
        $username,
        $hash,
        $role,
    ]);

    $newId = $pdo->lastInsertId();

    jsonResponse([
        'success'  => true,
        'message'  => 'User created.',
        'id'       => $newId,
        'position' => getPosition($role, $lName),
    ]);

} catch (PDOException $e) {
    error_log('[SAVE_USER ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to create user.'], 500);
}