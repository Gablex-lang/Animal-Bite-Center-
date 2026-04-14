<?php
// ============================================================
//  api/get_inventory.php
//  Returns all vaccine batches with stock > 0
//  Sorted by expiry ASC (FEFO order)
// ============================================================

require_once __DIR__ . '/../db.php';

requireLogin(); // Any role can view inventory

$pdo = getPDO();

try {
    $stmt = $pdo->query("
        SELECT
            id,
            vaccine_brand                       AS name,
            stock_quantity                      AS qty,
            price,
            stocked_date                        AS dateStocked,
            expiry_date                         AS expiry,
            IF(expiry_date < CURDATE(), 1, 0)   AS is_expired
        FROM vaccine
        ORDER BY vaccine_brand ASC, expiry_date ASC
    ");
    $batches = $stmt->fetchAll();

    jsonResponse(['success' => true, 'data' => $batches]);

} catch (PDOException $e) {
    error_log('[GET_INVENTORY ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch inventory.'], 500);
}