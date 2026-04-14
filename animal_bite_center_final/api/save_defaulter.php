<?php
// ============================================================
//  api/save_defaulter.php
//  Inserts a new record into defaulter_log
//  Only ADMIN and SUPERADMIN can mark a patient as defaulter
//  POST body: {
//    patient_id, incident_id, date_of_entry,
//    missed_dose, reason_for_default, action_taken, remarks
//  }
// ============================================================

require_once __DIR__ . '/../db.php';

$user = requireLogin(['ADMIN', 'SUPERADMIN']);
$pdo  = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body  = file_get_contents('php://input');
$input = json_decode($body, true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid request data.']);
}

$patientId        = isset($input['patient_id'])        ? (int)$input['patient_id']        : 0;
$incidentId       = isset($input['incident_id'])       ? (int)$input['incident_id']       : 0;
$dateOfEntry      = $input['date_of_entry']      ?? null;
$missedDose       = strtoupper(trim($input['missed_dose']       ?? ''));
$reasonForDefault = strtoupper(trim($input['reason_for_default'] ?? ''));
$actionTaken      = strtoupper(trim($input['action_taken']      ?? ''));
$remarks          = strtoupper(trim($input['remarks']           ?? ''));

// Validate required fields
if (!$patientId || !$incidentId || empty($dateOfEntry) || empty($reasonForDefault)) {
    jsonResponse(['success' => false, 'message' => 'Patient, incident, date of entry, and reason for default are required.']);
}

try {
    // Prevent duplicate defaulter entry for the same incident
    $check = $pdo->prepare("SELECT id FROM defaulter_log WHERE incident_id = ? LIMIT 1");
    $check->execute([$incidentId]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'message' => 'This patient has already been marked as a defaulter for this incident.']);
    }

    $stmt = $pdo->prepare("
        INSERT INTO defaulter_log
            (patient_id, incident_id, date_of_entry, missed_dose,
             reason_for_default, action_taken, remarks, staff_responsible_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $patientId,
        $incidentId,
        $dateOfEntry,
        $missedDose       ?: null,
        $reasonForDefault,
        $actionTaken      ?: null,
        $remarks          ?: null,
        $user['id'],
    ]);

    jsonResponse(['success' => true, 'message' => 'Patient marked as defaulter.']);

} catch (PDOException $e) {
    error_log('[SAVE_DEFAULTER ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to save defaulter record.'], 500);
}