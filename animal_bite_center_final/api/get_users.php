<?php
// ============================================================
//  api/get_users.php
//  Returns all system users — SUPERADMIN only
//  Passwords are NEVER returned
// ============================================================

require_once __DIR__ . '/../db.php';

requireLogin('SUPERADMIN');

$pdo = getPDO();

try {
    $stmt = $pdo->query("
        SELECT id, first_name, middle_name, last_name, username, role, created_at
        FROM users
        ORDER BY role ASC, last_name ASC
    ");
    $users = $stmt->fetchAll();

    $result = [];
    foreach ($users as $u) {
        $fullName = $u['middle_name']
            ? "{$u['last_name']}, {$u['first_name']} {$u['middle_name']}"
            : "{$u['last_name']}, {$u['first_name']}";

        $result[] = [
            'id'       => $u['id'],
            'fullName' => strtoupper($fullName),
            'username' => $u['username'],
            'role'     => $u['role'],
            'position' => getPosition($u['role'], $u['last_name']),
        ];
    }

    jsonResponse(['success' => true, 'data' => $result]);

} catch (PDOException $e) {
    error_log('[GET_USERS ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch users.'], 500);
}