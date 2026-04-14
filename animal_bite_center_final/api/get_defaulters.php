<?php
// ============================================================
//  api/get_defaulters.php
//  Returns all defaulter log records with full patient info
//  Only ADMIN and SUPERADMIN can view the defaulter logbook
// ============================================================

require_once __DIR__ . '/../db.php';

requireLogin(['ADMIN', 'SUPERADMIN']);

$pdo = getPDO();

try {
    $stmt = $pdo->query("
        SELECT
            dl.id,
            dl.date_of_entry,
            dl.missed_dose,
            dl.reason_for_default,
            dl.action_taken,
            dl.remarks,

            p.id            AS patient_id,
            p.last_name     AS lName,
            p.first_name    AS fName,
            p.middle_name   AS mName,
            p.age,
            p.sex,
            p.contact_no,

            bi.category,

            vs.day_0        AS d0,
            vs.day_3        AS d3,
            vs.day_7        AS d7,
            vs.day_14       AS d14,
            vs.day_28       AS d28,

            u.first_name    AS staff_fname,
            u.last_name     AS staff_lname,
            u.role          AS staff_role

        FROM defaulter_log dl
        JOIN patients p
            ON dl.patient_id = p.id
        JOIN bite_incident bi
            ON dl.incident_id = bi.id
        JOIN vaccination_schedules vs
            ON vs.incident_id = bi.id
        LEFT JOIN users u
            ON dl.staff_responsible_id = u.id
        ORDER BY dl.date_of_entry DESC, dl.id DESC
    ");

    $rows = $stmt->fetchAll();
    $data = [];

    foreach ($rows as $r) {
        $fullName = strtoupper($r['lName']) . ', ' . strtoupper($r['fName']);
        if (!empty($r['mName'])) $fullName .= ' ' . strtoupper($r['mName']);

        $staffDisplay = '';
        if (!empty($r['staff_lname'])) {
            $staffDisplay = getPosition($r['staff_role'], $r['staff_lname']);
        }

        $data[] = [
            'id'                 => $r['id'],
            'date_of_entry'      => $r['date_of_entry'],
            'patient_id'         => $r['patient_id'],
            'name'               => $fullName,
            'age'                => $r['age'],
            'gender'             => $r['sex'] === 'M' ? 'MALE' : 'FEMALE',
            'contact_no'         => $r['contact_no'] ?? '',
            'category'           => $r['category'],
            'missed_dose'        => $r['missed_dose'],
            'd0'                 => $r['d0'],
            'd3'                 => $r['d3'],
            'd7'                 => $r['d7'],
            'd14'                => $r['d14'],
            'd28'                => $r['d28'],
            'reason_for_default' => strtoupper($r['reason_for_default'] ?? ''),
            'action_taken'       => strtoupper($r['action_taken'] ?? ''),
            'remarks'            => strtoupper($r['remarks'] ?? ''),
            'staff_responsible'  => $staffDisplay,
        ];
    }

    jsonResponse(['success' => true, 'data' => $data]);

} catch (PDOException $e) {
    error_log('[GET_DEFAULTERS ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch defaulter records.'], 500);
}