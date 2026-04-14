<?php
// ============================================================
//  api/delete_patient.php
//  Soft-deletes (archives) a patient record
//  Only ADMIN and SUPERADMIN can archive
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
        SET is_archived    = 1,
            archived_date  = CURDATE(),
            archived_by_id = ?
        WHERE id = ?
          AND is_archived = 0
    ");
    $stmt->execute([$user['id'], $incidentId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Record not found or already archived.']);
    }

    jsonResponse(['success' => true, 'message' => 'Record archived.']);

} catch (PDOException $e) {
    error_log('[DELETE_PATIENT ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to archive record.'], 500);
}