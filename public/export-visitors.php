<?php
/**
 * Export Visitors Endpoint
 * Handles PDF and Excel export requests with database integration
 */

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/controllers/ExportController.php';

use QRIntercom\controllers\ExportController;

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get parameters
$format = $_GET['format'] ?? 'pdf'; // pdf or excel
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

// Fetch visitors from database
global $pdo;
$query = "SELECT v.id, v.name, v.mobile as contact, b.name as block, f.floor, u.name as resident_name, 
          v.check_in as visit_time, v.check_out as exit_time,
          CASE WHEN v.check_out IS NULL THEN 'inside' ELSE 'left' END as status
          FROM visitors v
          LEFT JOIN flats f ON v.flat_id = f.id
          LEFT JOIN blocks b ON f.block_id = b.id
          LEFT JOIN users u ON f.id = (SELECT flat_id FROM users LIMIT 1)
          WHERE 1=1";

$params = [];

if ($startDate) {
    $query .= " AND DATE(v.check_in) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $query .= " AND DATE(v.check_in) <= ?";
    $params[] = $endDate;
}

$query .= " ORDER BY v.check_in DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validate format
if (!in_array($format, ['pdf', 'excel'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid format. Use "pdf" or "excel"']);
    exit;
}

// Check if there's data to export
if (empty($visitors)) {
    http_response_code(404);
    echo json_encode(['error' => 'No visitors found for the selected date range']);
    exit;
}

// Export based on format
try {
    if ($format === 'excel') {
        ExportController::exportToExcel($visitors, $startDate, $endDate);
    } else {
        ExportController::exportToPDF($visitors, $startDate, $endDate);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Export failed: ' . $e->getMessage()]);
}
