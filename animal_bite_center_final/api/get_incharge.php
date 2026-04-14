<?php
// ============================================================
//  api/get_incharge.php
//  Returns all ADMIN and STAFF users for the incharge dropdown
//  SUPERADMIN is excluded (matches original JS logic)
// ============================================================

require_once __DIR__ . '/../db.php';

requireLogin();

$pdo = getPDO();

try {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, role
        FROM users
        WHERE role IN ('ADMIN', 'STAFF')
        ORDER BY last_name ASC
    ");
    $users = $stmt->fetchAll();

    $result = [];
    foreach ($users as $u) {
        $result[] = [
            'id'       => $u['id'],
            'position' => getPosition($u['role'], $u['last_name']),
        ];
    }

    jsonResponse(['success' => true, 'data' => $result]);

} catch (PDOException $e) {
    error_log('[GET_INCHARGE ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch incharge list.'], 500);
}