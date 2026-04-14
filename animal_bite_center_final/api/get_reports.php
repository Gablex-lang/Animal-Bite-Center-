<?php
// ============================================================
//  api/get_reports.php
//  Returns finance report data
//  Only ADMIN and SUPERADMIN can access reports
//  Query params:
//    ?type=finance&range=all|specific|week|month|year&date=YYYY-MM-DD
// ============================================================

require_once __DIR__ . '/../db.php';

requireLogin(['ADMIN', 'SUPERADMIN']);

$pdo    = getPDO();
$params = [];

try {
    if (true) { // finance only
        // ── Finance report filtered by date range ─────────────
        $range      = $_GET['range'] ?? 'all';
        $specificDate = $_GET['date'] ?? '';

        $sql = "
            SELECT
                v.vaccine_brand             AS brand,
                pt.price_at_registration    AS price,
                bi.date_registered          AS regDate
            FROM bite_incident bi
            JOIN pep_treatment pt  ON pt.incident_id = bi.id
            JOIN vaccine v         ON pt.vaccine_id = v.id
            WHERE bi.is_archived = 0
        ";

        switch ($range) {
            case 'specific':
                if (!empty($specificDate)) {
                    $sql .= " AND bi.date_registered = ?";
                    $params[] = $specificDate;
                }
                break;
            case 'week':
                $sql .= " AND YEARWEEK(bi.date_registered, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                $sql .= " AND YEAR(bi.date_registered) = YEAR(CURDATE())
                          AND MONTH(bi.date_registered) = MONTH(CURDATE())";
                break;
            case 'year':
                $sql .= " AND YEAR(bi.date_registered) = YEAR(CURDATE())";
                break;
            // 'all' — no filter
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Group by brand
        $brandStats   = [];
        $totalRevenue = 0;

        foreach ($rows as $r) {
            $brand = strtoupper($r['brand']);
            $price = (float)$r['price'];

            if (!isset($brandStats[$brand])) {
                $brandStats[$brand] = ['count' => 0, 'total' => 0];
            }
            $brandStats[$brand]['count']++;
            $brandStats[$brand]['total'] += $price;
            $totalRevenue += $price;
        }

        $data = [];
        foreach ($brandStats as $brand => $stats) {
            $data[] = [
                'brand' => $brand,
                'count' => $stats['count'],
                'total' => number_format($stats['total'], 2),
            ];
        }

        jsonResponse([
            'success'      => true,
            'data'         => $data,
            'totalRevenue' => number_format($totalRevenue, 2),
        ]);
    }

} catch (PDOException $e) {
    error_log('[GET_REPORTS ERROR] ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch report data.'], 500);
}