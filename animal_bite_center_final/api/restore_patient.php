<?php
// ============================================================
//  api/restore_patient.php
//  Restores an archived patient back to active
//  Only ADMIN and SUPERADMIN can restore
//  POST body: { incident_id: int }
// ============================================================

require_once __DIR__ . '/../db.php';

$user = requireLogin(['ADMIN', 'SUPERADMIN']);
$pdo  = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body       = file_get_contents('php://input');
$input      = json_decode($body, true);
$incidentId = isset($input['incident_id']) ? (int)$input['incident_id'] : 0;

if (!$incidentId) {
    jsonResponse(['success' => false, 'message' => 'Invalid incident ID.']);
}

try {
    $stmt = $pdo->prepare("
        UPDATE bite_incident
        SET is_archived    = 0,
            archived_date  = NULL,
            archived_by_id = NULL
        WHERE id = ?
          AND is_archived = 1
    ");
    $stmt->execute([$incidentId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Record not found or not archived.']);
    }

    jsonResponse(['success' => true, 'message' => 'Record restored.']);

} catch (PDOException $e) {
    error_log('[RESTORE_PATIENT ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to restore record.'], 500);
}