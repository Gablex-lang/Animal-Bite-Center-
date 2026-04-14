<?php
// ============================================================
//  api/get_patients.php
//  Returns all active (non-archived) patient records via JOIN
//  Query param: ?archived=1  → returns archived records instead
// ============================================================

require_once __DIR__ . '/../db.php';

requireLogin(); // Any logged-in role can fetch patients

$pdo        = getPDO();
$archived   = isset($_GET['archived']) && $_GET['archived'] == '1' ? 1 : 0;

try {
    $stmt = $pdo->prepare("
        SELECT
            bi.id                       AS incident_id,
            bi.date_registered          AS regDate,
            bi.bite_date                AS bDate,
            bi.place_occurred           AS place,
            bi.animal_type              AS animal,
            bi.bite_type                AS bType,
            bi.body_site                AS site,
            bi.category                 AS cat,
            bi.rig_date                 AS rig,
            bi.is_archived,
            bi.archived_date            AS dateDeleted,

            p.id                        AS patient_id,
            p.last_name                 AS lName,
            p.first_name                AS fName,
            p.middle_name               AS mName,
            p.age,
            p.sex,
            p.address                   AS addr,
            p.contact_no                AS contactNo,

            v.id                        AS vaccine_id,
            v.vaccine_brand             AS brand,
            v.price                     AS vaccinePrice,

            pt.id                       AS treatment_id,
            pt.washed                   AS wash,
            pt.injection_route          AS route,
            pt.outcome,
            pt.animal_status            AS aStatus,
            pt.remarks,
            pt.price_at_registration    AS currentPrice,
            pt.injection_incharge_id    AS inchargeId,

            vs.id                       AS schedule_id,
            vs.day_0                    AS d0,
            vs.day_3                    AS d3,
            vs.day_7                    AS d7,
            vs.day_14                   AS d14,
            vs.day_28                   AS d28,

            u_ic.id                     AS ic_id,
            u_ic.first_name             AS ic_fname,
            u_ic.last_name              AS ic_lname,
            u_ic.role                   AS ic_role,

            u_reg.id                    AS reg_id,
            u_reg.first_name            AS reg_fname,
            u_reg.last_name             AS reg_lname,
            u_reg.role                  AS reg_role,

            IF(dl.id IS NOT NULL, 1, 0) AS is_defaulted

        FROM bite_incident bi
        JOIN patients p
            ON bi.patient_id = p.id
        JOIN pep_treatment pt
            ON pt.incident_id = bi.id
        JOIN vaccine v
            ON pt.vaccine_id = v.id
        JOIN vaccination_schedules vs
            ON pt.vaccination_schedule_id = vs.id
        LEFT JOIN users u_ic
            ON pt.injection_incharge_id = u_ic.id
        LEFT JOIN users u_reg
            ON bi.recorded_by_id = u_reg.id
        LEFT JOIN defaulter_log dl
            ON dl.incident_id = bi.id
        WHERE bi.is_archived = ?
        ORDER BY bi.date_registered ASC, bi.id ASC
    ");

    $stmt->execute([$archived]);
    $rows = $stmt->fetchAll();

    // Build clean response array matching the JS field names
    $patients = [];
    foreach ($rows as $r) {
        // Build incharge display name
        $inchargeDisplay = '';
        if (!empty($r['ic_lname'])) {
            $inchargeDisplay = getPosition($r['ic_role'], $r['ic_lname']);
        }

        // Build registeredBy display name
        $registeredBy = '';
        if (!empty($r['reg_lname'])) {
            $registeredBy = getPosition($r['reg_role'], $r['reg_lname']);
        }

        $patients[] = [
            // IDs (needed for edit/delete operations)
            'incident_id'    => $r['incident_id'],
            'patient_id'     => $r['patient_id'],
            'treatment_id'   => $r['treatment_id'],
            'schedule_id'    => $r['schedule_id'],
            'vaccine_id'     => $r['vaccine_id'],

            // Patient info
            'lName'          => strtoupper($r['lName'] ?? ''),
            'fName'          => strtoupper($r['fName'] ?? ''),
            'mName'          => strtoupper($r['mName'] ?? ''),
            'age'            => $r['age'],
            'sex'            => $r['sex'],
            'addr'           => strtoupper($r['addr'] ?? ''),
            'contactNo'      => $r['contactNo'] ?? '',

            // Bite incident
            'regDate'        => $r['regDate'],
            'bDate'          => $r['bDate'],
            'place'          => strtoupper($r['place'] ?? ''),
            'animal'         => strtoupper($r['animal'] ?? ''),
            'bType'          => $r['bType'],
            'site'           => strtoupper($r['site'] ?? ''),
            'cat'            => $r['cat'],
            'rig'            => $r['rig'],

            // Treatment
            'wash'           => $r['wash'],
            'brand'          => strtoupper($r['brand'] ?? ''),
            'route'          => strtoupper($r['route'] ?? ''),
            'outcome'        => $r['outcome'],
            'aStatus'        => $r['aStatus'],
            'remarks'        => strtoupper($r['remarks'] ?? ''),
            'currentPrice'   => $r['currentPrice'],

            // Vaccine schedule
            'd0'             => $r['d0'],
            'd3'             => $r['d3'],
            'd7'             => $r['d7'],
            'd14'            => $r['d14'],
            'd28'            => $r['d28'],

            // Accountability
            'inchargeId'      => $r['inchargeId'],
            'inchargeDisplay' => $inchargeDisplay,
            'registeredBy'    => $registeredBy,

            // Archive info
            'dateDeleted'    => $r['dateDeleted'],
            'is_defaulted'   => (int)$r['is_defaulted'],
        ];
    }

    jsonResponse(['success' => true, 'data' => $patients]);

} catch (PDOException $e) {
    error_log('[GET_PATIENTS ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch patients.'], 500);
}