<?php
// ============================================================
//  api/delete_user.php
//  Deletes a user — SUPERADMIN only
//  Cannot delete own account or other SUPERADMIN accounts
//  POST body: { user_id: int }
// ============================================================

require_once __DIR__ . '/../db.php';

$currentUser = requireLogin('SUPERADMIN');
$pdo         = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body   = file_get_contents('php://input');
$input  = json_decode($body, true);
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if (!$userId) {
    jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
}

// Cannot delete yourself
if ($userId === (int)$currentUser['id']) {
    jsonResponse(['success' => false, 'message' => 'You cannot delete your own account.']);
}

try {
    // Check target user exists and is not SUPERADMIN
    $check = $pdo->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
    $check->execute([$userId]);
    $target = $check->fetch();

    if (!$target) {
        jsonResponse(['success' => false, 'message' => 'User not found.']);
    }

    if ($target['role'] === 'SUPERADMIN') {
        jsonResponse(['success' => false, 'message' => 'Cannot delete a SUPERADMIN account.']);
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

    jsonResponse(['success' => true, 'message' => 'User deleted.']);

} catch (PDOException $e) {
    error_log('[DELETE_USER ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to delete user.'], 500);
}