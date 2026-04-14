<?php
// ============================================================
//  api/save_batch.php
//  INSERT or UPDATE a vaccine batch
//  Only ADMIN and SUPERADMIN can manage inventory
//  POST body: { id: null|int, name, qty, dateStocked, expiry, price }
// ============================================================

require_once __DIR__ . '/../db.php';

$user = requireLogin(['ADMIN', 'SUPERADMIN']);
$pdo  = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body  = file_get_contents('php://input');
$input = json_decode($body, true);

$id          = !empty($input['id']) ? (int)$input['id'] : null;
$name        = strtoupper(trim($input['name'] ?? ''));
$qty         = (int)($input['qty'] ?? 0);
$dateStocked = $input['dateStocked'] ?? null;
$expiry      = $input['expiry'] ?? null;
$price       = (float)($input['price'] ?? 0);

if (empty($name) || $qty < 0 || empty($dateStocked) || empty($expiry) || $price < 0) {
    jsonResponse(['success' => false, 'message' => 'All batch fields are required.']);
}

try {
    if ($id === null) {
        // INSERT new batch
        $stmt = $pdo->prepare("
            INSERT INTO vaccine (vaccine_brand, stock_quantity, price, stocked_date, expiry_date, added_by_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $qty, $price, $dateStocked, $expiry, $user['id']]);
        $newId = $pdo->lastInsertId();
        jsonResponse(['success' => true, 'message' => 'Batch saved.', 'id' => $newId]);
    } else {
        // UPDATE existing batch
        $stmt = $pdo->prepare("
            UPDATE vaccine
            SET vaccine_brand   = ?,
                stock_quantity  = ?,
                price           = ?,
                stocked_date    = ?,
                expiry_date     = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $qty, $price, $dateStocked, $expiry, $id]);
        jsonResponse(['success' => true, 'message' => 'Batch updated.']);
    }

} catch (PDOException $e) {
    error_log('[SAVE_BATCH ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to save batch.'], 500);
}