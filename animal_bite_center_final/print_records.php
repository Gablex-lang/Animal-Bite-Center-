<?php
// ============================================================
//  print_records.php
//  Printable All Records — ADMIN & SUPERADMIN only
//  Minimalist DOH Rabies Exposure Registry format
// ============================================================

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.html'); exit;
}

$role = $_SESSION['user_role'] ?? '';
if (!in_array($role, ['ADMIN', 'SUPERADMIN'], true)) {
    header('Location: login.html'); exit;
}

$pdo = getPDO();

// ── Filters ───────────────────────────────────────────────────
$dateFrom = !empty($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo   = !empty($_GET['date_to'])   ? $_GET['date_to']   : '';
$outcome  = !empty($_GET['outcome'])   ? $_GET['outcome']   : '';
$brand    = !empty($_GET['brand'])     ? strtoupper(trim($_GET['brand'])) : '';

// ── Query ─────────────────────────────────────────────────────
$params = [];
$sql = "
    SELECT
        bi.date_registered          AS regDate,
        bi.bite_date                AS bDate,
        bi.place_occurred           AS place,
        bi.animal_type              AS animal,
        bi.bite_type                AS bType,
        bi.body_site                AS site,
        bi.category                 AS cat,
        bi.rig_date                 AS rig,

        p.last_name                 AS lName,
        p.first_name                AS fName,
        p.middle_name               AS mName,
        p.age,
        p.sex,
        p.address                   AS addr,

        v.vaccine_brand             AS brand,

        pt.washed                   AS wash,
        pt.injection_route          AS route,
        pt.outcome,
        pt.animal_status            AS aStatus,
        pt.remarks,
        pt.price_at_registration    AS priceAtReg,

        vs.day_0, vs.day_3, vs.day_7, vs.day_14, vs.day_28

    FROM bite_incident bi
    JOIN patients p               ON bi.patient_id = p.id
    JOIN pep_treatment pt         ON pt.incident_id = bi.id
    JOIN vaccine v                ON pt.vaccine_id = v.id
    JOIN vaccination_schedules vs ON pt.vaccination_schedule_id = vs.id
    LEFT JOIN users u_ic          ON pt.injection_incharge_id = u_ic.id
    LEFT JOIN users u_reg         ON bi.recorded_by_id = u_reg.id
    WHERE bi.is_archived = 0
";

if (!empty($dateFrom)) { $sql .= " AND bi.date_registered >= ?"; $params[] = $dateFrom; }
if (!empty($dateTo))   { $sql .= " AND bi.date_registered <= ?"; $params[] = $dateTo; }
if (!empty($outcome))  { $sql .= " AND pt.outcome = ?";          $params[] = $outcome; }
if (!empty($brand))    { $sql .= " AND UPPER(v.vaccine_brand) = ?"; $params[] = $brand; }

$sql .= " ORDER BY bi.date_registered ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Brand list for filter dropdown
$brandStmt = $pdo->query("SELECT DISTINCT vaccine_brand FROM vaccine ORDER BY vaccine_brand ASC");
$allBrands = $brandStmt->fetchAll(PDO::FETCH_COLUMN);

// ── Helpers ───────────────────────────────────────────────────
function fmtDate($str) {
    if (!$str || trim($str) === '') return '';
    $d = DateTime::createFromFormat('Y-m-d', $str);
    return $d ? $d->format('m/d/Y') : $str;
}
function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$totalCount  = count($records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rabies Exposure Registry</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #ddd;
        }

        /* ─────────────────────────────
           SCREEN-ONLY TOOLBAR
        ───────────────────────────── */
        .toolbar {
            background: #fff;
            border-bottom: 1px solid #999;
            padding: 8px 16px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .toolbar-title {
            font-size: 13px;
            font-weight: bold;
            color: #000;
        }

        .toolbar-right {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .fg { display: flex; flex-direction: column; gap: 2px; }

        .fg label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.3px;
        }

        .fg input,
        .fg select {
            padding: 4px 8px;
            border: 1px solid #aaa;
            font-size: 11px;
            height: 28px;
            background: #fff;
            color: #000;
            outline: none;
            font-family: Arial, sans-serif;
        }
        .fg input:focus,
        .fg select:focus { border-color: #333; }

        .tbtn {
            height: 28px;
            padding: 0 14px;
            border: 1px solid #555;
            background: #444;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            font-family: Arial, sans-serif;
            letter-spacing: 0.3px;
        }
        .tbtn:hover { background: #222; }
        .tbtn.out {
            background: #fff;
            color: #333;
            border: 1px solid #aaa;
        }
        .tbtn.out:hover { background: #f5f5f5; }

        /* ─────────────────────────────
           PAGE WRAPPER
        ───────────────────────────── */
        .page-wrapper {
            max-width: 1440px;
            margin: 20px auto;
            padding: 0 16px 40px;
        }

        /* ─────────────────────────────
           DOH HEADER  — plain, no color
        ───────────────────────────── */
        .doh-header {
            background: #fff;
            border: 1px solid #000;
            border-bottom: none;
            padding: 12px 10px 8px;
            text-align: center;
        }

        .doh-header p {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.75;
            color: #000;
        }

        .doh-header .reg-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
            margin-bottom: 8px;
            color: #000;
        }

        /* ─────────────────────────────
           REGISTRY TABLE
           Matches reference image exactly
        ───────────────────────────── */
        .table-wrap { overflow-x: auto; }

        table.reg {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            font-size: 10px;
            table-layout: auto;
        }

        /* ALL borders use same thin black line — matching reference */
        table.reg th,
        table.reg td {
            border: 1px solid #000;
            padding: 4px 4px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.3;
            color: #000;
        }

        /* Group header — white background, normal weight, same as reference */
        table.reg thead tr.grp th {
            background: #fff;
            font-weight: bold;
            font-size: 10px;
            padding: 5px 4px;
            vertical-align: bottom;
        }

        /* Sub-header — white background (no grey tint), same font size */
        table.reg thead tr.sub th {
            background: #fff;
            font-weight: bold;
            font-size: 10px;
            padding: 4px 4px;
        }

        /* Data cells */
        table.reg td.t-left { text-align: left; }
        table.reg td.t-name { text-align: left; font-size: 10px; white-space: nowrap; }
        table.reg td.t-addr { text-align: left; font-size: 9px; }
        table.reg td.t-sm   { font-size: 9px; white-space: nowrap; }

        /* Subtle alternating rows */
        table.reg tbody tr:nth-child(even) td { background: #f9f9f9; }

        /* Empty rows — keep consistent height */
        table.reg tbody tr td { min-height: 20px; height: 20px; }

        /* Footer summary row */
        table.reg tfoot td {
            font-size: 10px;
            font-weight: bold;
            padding: 6px 8px;
            text-align: center;
            border-top: 2px solid #000;
            background: #fff;
        }

        .no-records {
            background: #fff;
            border: 1px solid #000;
            padding: 40px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
        }

        /* ─────────────────────────────
           PRINT STYLES
        ───────────────────────────── */
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 5mm;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body       { background: #fff; font-size: 8px; }
            .toolbar   { display: none !important; }

            .page-wrapper {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            .doh-header {
                border: 1px solid #000;
                border-bottom: none;
                padding: 8px 6px 6px;
            }
            .doh-header p         { font-size: 9px; line-height: 1.6; }
            .doh-header .reg-title { font-size: 11px; margin-bottom: 6px; }
            .print-meta            { font-size: 8px; gap: 12px; padding-top: 4px; }

            .table-wrap { overflow: visible; }

            table.reg {
                font-size: 7px;
                page-break-inside: auto;
            }

            table.reg thead { display: table-header-group; }
            table.reg tfoot { display: table-footer-group; }

            table.reg th,
            table.reg td   { padding: 2px 2px; }

            table.reg thead tr.grp th { font-size: 7px; padding: 3px 2px; }
            table.reg thead tr.sub th { font-size: 7px; padding: 3px 2px; }

            table.reg td.t-name { font-size: 7px; }
            table.reg td.t-addr { font-size: 6px; }
            table.reg td.t-sm   { font-size: 6px; }

            table.reg tbody tr:nth-child(even) td { background: #f5f5f5 !important; }
            table.reg tbody tr td { height: 14px; }

            table.reg tfoot td { font-size: 7px; padding: 3px 4px; }
        }
    </style>
</head>
<body>

<!-- SCREEN-ONLY TOOLBAR -->
<div class="toolbar">
    <div class="toolbar-title">Rabies Exposure Registry &mdash; Print Preview</div>
    <div class="toolbar-right">
        <form method="GET" style="display:contents;">
            <div class="fg">
                <label>Date From</label>
                <input type="date" name="date_from" value="<?= esc($dateFrom) ?>">
            </div>
            <div class="fg">
                <label>Date To</label>
                <input type="date" name="date_to" value="<?= esc($dateTo) ?>">
            </div>
            <div class="fg">
                <label>Outcome</label>
                <select name="outcome">
                    <option value="">All Outcomes</option>
                    <option value="C"   <?= $outcome==='C'   ? 'selected' : '' ?>>C (Complete)</option>
                    <option value="INC" <?= $outcome==='INC' ? 'selected' : '' ?>>INC (Incomplete)</option>
                    <option value="N"   <?= $outcome==='N'   ? 'selected' : '' ?>>N (Non-compliant)</option>
                    <option value="D"   <?= $outcome==='D'   ? 'selected' : '' ?>>D (Died)</option>
                </select>
            </div>
            <div class="fg">
                <label>Vaccine Brand</label>
                <select name="brand">
                    <option value="">All Brands</option>
                    <?php foreach ($allBrands as $b): ?>
                    <option value="<?= esc($b) ?>" <?= strtoupper($brand)===strtoupper($b) ? 'selected' : '' ?>>
                        <?= esc($b) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="tbtn">Filter</button>
        </form>
        <button onclick="window.print()" class="tbtn">&#128438; Print</button>
        <a href="javascript:history.back()" class="tbtn out"
           style="display:flex;align-items:center;text-decoration:none;">&larr; Back</a>
    </div>
</div>

<!-- PRINTABLE CONTENT -->
<div class="page-wrapper">

    <!-- DOH HEADER — plain text, centered, no colors -->
    <div class="doh-header">
        <p>Department of Health</p>
        <p>National Rabies Prevention and Control Program</p>
        <p class="reg-title">Rabies Exposure Registry</p>
    </div>

    <?php if (empty($records)): ?>
    <div class="no-records">No records found.</div>

    <?php else: ?>
    <div class="table-wrap">
        <table class="reg">
            <thead>
                <!--
                    Column order matches reference image exactly:
                    Registration (No. | Date) | Name | Address | Age | Sex | Date(Bite) |
                    Place | Type of Animal | Type(B/NB) | Site |
                    Category | Washing of Bite | RIG Date Given |
                    PEP: Route | D0 | D3 | D7 | D14(IM) | D28 | Brand Name |
                    Outcome | Biting Animal Status | Remarks
                -->
                <tr class="grp">
                    <th colspan="2">Registration</th>
                    <th rowspan="2">Name of Patient</th>
                    <th rowspan="2">Address</th>
                    <th rowspan="2">Age</th>
                    <th rowspan="2">Sex</th>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Place (Where<br>Biting Occurred)</th>
                    <th rowspan="2">Type of<br>Animal</th>
                    <th rowspan="2">Type<br>(B/NB)</th>
                    <th rowspan="2">Site<br>(Body Part)</th>
                    <th rowspan="2">Category<br>(1, 2, and 3)</th>
                    <th rowspan="2">Washing of Bite<br>(Y/N)</th>
                    <th rowspan="2">RIG<br>Date Given</th>
                    <th colspan="7">Post Exposure Prophylaxis (PEP)<br>Tissue Culture Vaccine (Date Given)</th>
                    <th rowspan="2">Outcome<br>(C/Inc/N/D)</th>
                    <th rowspan="2">Biting Animal Status<br>(after 14 days)<br>(Alive/Dead/Lost)</th>
                    <th rowspan="2">Remarks</th>
                </tr>
                <tr class="sub">
                    <th>No.</th>
                    <th>Date</th>
                    <th>Route</th>
                    <th>D0</th>
                    <th>D3</th>
                    <th>D7</th>
                    <th>D14(IM)</th>
                    <th>D28</th>
                    <th>Brand Name</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $i => $r):
                // Build full name: LAST, FIRST M.
                $mi       = ($r['mName'] && trim($r['mName']) !== '')
                            ? ' ' . strtoupper(substr(trim($r['mName']), 0, 1)) . '.'
                            : '';
                $fullName = strtoupper(trim($r['lName'])) . ', '
                          . strtoupper(trim($r['fName'])) . $mi;

                // Each field — show actual value, empty string if null/blank
                $cat    = ($r['cat']  !== null && $r['cat']  !== '') ? esc($r['cat'])  : '';
                $wash   = ($r['wash'] !== null && $r['wash'] !== '') ? esc($r['wash']) : '';
                $rig    = fmtDate($r['rig']);
                $route  = strtoupper(trim($r['route'] ?? ''));
                $d0     = fmtDate($r['day_0']);
                $d3     = fmtDate($r['day_3']);
                $d7     = fmtDate($r['day_7']);
                $d14    = fmtDate($r['day_14']);
                $d28    = fmtDate($r['day_28']);
                $remarks= strtoupper(trim($r['remarks'] ?? ''));
            ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td class="t-sm"><?= esc(fmtDate($r['regDate'])) ?></td>
                    <td class="t-name"><?= esc($fullName) ?></td>
                    <td class="t-addr"><?= esc(strtoupper(trim($r['addr']))) ?></td>
                    <td><?= esc($r['age']) ?></td>
                    <td><?= esc($r['sex']) ?></td>
                    <td class="t-sm"><?= esc(fmtDate($r['bDate'])) ?></td>
                    <td><?= esc(strtoupper(trim($r['place']))) ?></td>
                    <td><?= esc(strtoupper(trim($r['animal']))) ?></td>
                    <td><?= esc($r['bType']) ?></td>
                    <td><?= esc(strtoupper(trim($r['site']))) ?></td>
                    <td><?= $cat ?></td>
                    <td><?= $wash ?></td>
                    <td class="t-sm"><?= esc($rig) ?></td>
                    <td><?= esc($route) ?></td>
                    <td class="t-sm"><?= esc($d0) ?></td>
                    <td class="t-sm"><?= esc($d3) ?></td>
                    <td class="t-sm"><?= esc($d7) ?></td>
                    <td class="t-sm"><?= esc($d14) ?></td>
                    <td class="t-sm"><?= esc($d28) ?></td>
                    <td><?= esc(strtoupper(trim($r['brand']))) ?></td>
                    <td><?= esc($r['outcome']) ?></td>
                    <td><?= esc($r['aStatus']) ?></td>
                    <td class="t-left"><?= esc($remarks) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div><!-- end .page-wrapper -->

</body>
</html>