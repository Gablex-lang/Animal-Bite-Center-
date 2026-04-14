<?php
// ============================================================
//  db.php  —  PHP 7.4+ compatible (no union types)
//  PDO Database Connection — Animal Bite Center
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'animal_bite_center_db'); // ← match your DB name exactly
define('DB_USER',    'root');
define('DB_PASS',    '');                       // ← your MySQL password (blank for XAMPP default)
define('DB_CHARSET', 'utf8mb4');

function getPDO() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[ABTC DB ERROR] ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed. Please contact the administrator.'
            ]));
        }
    }
    return $pdo;
}

function jsonResponse(array $data, int $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// PHP 7 compatible — no string|array union type
function requireLogin($roles = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['user_id'])) {
        $isApi = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                 (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);
        if ($isApi) {
            jsonResponse(['success' => false, 'message' => 'Not authenticated.'], 401);
        }
        header('Location: ../login.html');
        exit;
    }

    if (!empty($roles)) {
        $allowed = is_array($roles) ? $roles : [$roles];
        if (!in_array($_SESSION['user_role'], $allowed, true)) {
            $isApi = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                     (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);
            if ($isApi) {
                jsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
            }
            header('Location: ../login.html');
            exit;
        }
    }

    return [
        'id'       => $_SESSION['user_id'],
        'role'     => $_SESSION['user_role'],
        'position' => $_SESSION['user_position'],
        'name'     => $_SESSION['user_name'],
    ];
}

// PHP 7 compatible — no match expression
function getPosition($role, $lastName) {
    if ($role === 'STAFF')      return 'Nurse ' . $lastName;
    if ($role === 'ADMIN')      return 'Dr. '   . $lastName;
    if ($role === 'SUPERADMIN') return 'Dr. '   . $lastName;
    return $lastName;
}