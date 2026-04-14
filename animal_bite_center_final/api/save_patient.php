<?php
// ============================================================
//  api/save_patient.php
//  INSERT new patient (4-table transaction + FEFO deduct)
//  UPDATE existing patient (updates all 4 tables)
//  POST body JSON:
//    { incident_id: null|int, patient_id: null|int,
//      treatment_id: null|int, schedule_id: null|int,
//      ...all form fields... }
// ============================================================

require_once __DIR__ . '/../db.php';

$user = requireLogin(); // Any role can save patients
$pdo  = getPDO();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$body  = file_get_contents('php://input');
$input = json_decode($body, true);

if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid request data.']);
}

// ── Helper: safely get input value ───────────────────────────
function val(array $input, string $key, $default = ''): mixed {
    $v = $input[$key] ?? $default;
    return ($v === '' || $v === null) ? $default : $v;
}

function dateVal(array $input, string $key): ?string {
    $v = $input[$key] ?? '';
    return ($v === '' || $v === null) ? null : $v;
}

// ── Determine if this is INSERT or UPDATE ────────────────────
$isUpdate   = !empty($input['incident_id']);
$incidentId = $isUpdate ? (int)$input['incident_id'] : null;
$patientId  = $isUpdate ? (int)$input['patient_id']  : null;
$treatmentId= $isUpdate ? (int)$input['treatment_id']: null;
$scheduleId = $isUpdate ? (int)$input['schedule_id'] : null;

// ── Collect all field values ──────────────────────────────────
$lName   = strtoupper(trim(val($input, 'lName')));
$fName   = strtoupper(trim(val($input, 'fName')));
$mName   = strtoupper(trim(val($input, 'mName', '')));
$age     = (int)val($input, 'age', 0);
$sex     = val($input, 'sex', 'M');
$addr    = strtoupper(trim(val($input, 'addr')));
$contactNo = trim(val($input, 'contactNo', ''));

$regDate = dateVal($input, 'regDate');
$bDate   = dateVal($input, 'bDate');
$place   = strtoupper(trim(val($input, 'place')));
$animal  = strtoupper(trim(val($input, 'animal')));
$bType   = val($input, 'bType', 'B');
$site    = strtoupper(trim(val($input, 'site')));
$cat     = val($input, 'cat', '1');
$rig     = dateVal($input, 'rig');

$brand       = strtoupper(trim(val($input, 'brand')));
$route       = strtoupper(trim(val($input, 'route')));
$wash        = val($input, 'wash', 'N');
$outcome     = val($input, 'outcome', 'INC');
$aStatus     = val($input, 'aStatus', 'ALIVE');
$remarks     = strtoupper(trim(val($input, 'remarks', '')));
$inchargeId  = !empty($input['inchargeId']) ? (int)$input['inchargeId'] : null;

$d0  = dateVal($input, 'd0');
$d3  = dateVal($input, 'd3');
$d7  = dateVal($input, 'd7');
$d14 = dateVal($input, 'd14');
$d28 = dateVal($input, 'd28');

// ── Validate required fields ──────────────────────────────────
if (empty($lName) || empty($fName) || empty($regDate) || empty($brand)) {
    jsonResponse(['success' => false, 'message' => 'Missing required fields.']);
}

// ── Begin transaction ─────────────────────────────────────────
try {
    $pdo->beginTransaction();

    if (!$isUpdate) {
        // ════════════════════════════════════════════════════════
        //  INSERT NEW PATIENT
        // ════════════════════════════════════════════════════════

        // 1. Find correct vaccine batch (FEFO: earliest expiry with stock)
        $stmtV = $pdo->prepare("
            SELECT id, price
            FROM vaccine
            WHERE vaccine_brand = ?
              AND stock_quantity > 0
              AND expiry_date >= CURDATE()
            ORDER BY expiry_date ASC
            LIMIT 1
        ");
        $stmtV->execute([$brand]);
        $batch = $stmtV->fetch();

        if (!$batch) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'No valid stock available for the selected vaccine brand. The batch may be out of stock or expired.']);
        }

        $vaccineId    = $batch['id'];
        $lockedPrice  = $batch['price'];

        // 2. Deduct 1 from vaccine stock
        $pdo->prepare("
            UPDATE vaccine
            SET stock_quantity = stock_quantity - 1
            WHERE id = ?
        ")->execute([$vaccineId]);

        // 3. Insert into patients
        $stmtP = $pdo->prepare("
            INSERT INTO patients (first_name, middle_name, last_name, age, sex, address, contact_no)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtP->execute([$fName, $mName ?: null, $lName, $age, $sex, $addr, $contactNo ?: null]);
        $patientId = (int)$pdo->lastInsertId();

        // 4. Insert into bite_incident
        $stmtBI = $pdo->prepare("
            INSERT INTO bite_incident
                (patient_id, recorded_by_id, date_registered, bite_date,
                 place_occurred, animal_type, bite_type, body_site, category, rig_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtBI->execute([
            $patientId, $user['id'], $regDate, $bDate,
            $place, $animal, $bType, $site, $cat, $rig
        ]);
        $incidentId = (int)$pdo->lastInsertId();

        // 5. Insert into vaccination_schedules
        $stmtVS = $pdo->prepare("
            INSERT INTO vaccination_schedules (incident_id, day_0, day_3, day_7, day_14, day_28)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtVS->execute([$incidentId, $d0, $d3, $d7, $d14, $d28]);
        $scheduleId = (int)$pdo->lastInsertId();

        // 6. Insert into pep_treatment
        $stmtPT = $pdo->prepare("
            INSERT INTO pep_treatment
                (patient_id, incident_id, vaccine_id, injection_incharge_id,
                 washed, injection_route, vaccination_schedule_id,
                 outcome, animal_status, remarks, price_at_registration)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtPT->execute([
            $patientId, $incidentId, $vaccineId, $inchargeId,
            $wash, $route, $scheduleId,
            $outcome, $aStatus, $remarks ?: null, $lockedPrice
        ]);

    } else {
        // ════════════════════════════════════════════════════════
        //  UPDATE EXISTING PATIENT
        // ════════════════════════════════════════════════════════

        // 1. Update patients table
        $pdo->prepare("
            UPDATE patients
            SET first_name = ?, middle_name = ?, last_name = ?,
                age = ?, sex = ?, address = ?, contact_no = ?
            WHERE id = ?
        ")->execute([$fName, $mName ?: null, $lName, $age, $sex, $addr, $contactNo ?: null, $patientId]);

        // 2. Update bite_incident
        $pdo->prepare("
            UPDATE bite_incident
            SET date_registered = ?, bite_date = ?,
                place_occurred = ?, animal_type = ?,
                bite_type = ?, body_site = ?,
                category = ?, rig_date = ?
            WHERE id = ?
        ")->execute([
            $regDate, $bDate, $place, $animal,
            $bType, $site, $cat, $rig,
            $incidentId
        ]);

        // 3. Update vaccination_schedules
        $pdo->prepare("
            UPDATE vaccination_schedules
            SET day_0 = ?, day_3 = ?, day_7 = ?,
                day_14 = ?, day_28 = ?
            WHERE id = ?
        ")->execute([$d0, $d3, $d7, $d14, $d28, $scheduleId]);

        // 4. Update pep_treatment
        $pdo->prepare("
            UPDATE pep_treatment
            SET injection_incharge_id = ?,
                washed = ?, injection_route = ?,
                outcome = ?, animal_status = ?,
                remarks = ?
            WHERE id = ?
        ")->execute([
            $inchargeId, $wash, $route,
            $outcome, $aStatus, $remarks ?: null,
            $treatmentId
        ]);
    }

    $pdo->commit();

    jsonResponse([
        'success'     => true,
        'message'     => $isUpdate ? 'Record updated.' : 'Patient registered.',
        'incident_id' => $incidentId,
        'patient_id'  => $patientId,
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[SAVE_PATIENT ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to save patient record.'], 500);
}