<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Authentifizierung prüfen
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

// Include der Settings-Klasse
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    // JSON-Input lesen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Ungültige Anfrage');
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'set_debug_time':
            $date = !empty($input['date']) ? $input['date'] : null;
            $time = !empty($input['time']) ? $input['time'] : null;
            
            // Validierung
            if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new Exception('Ungültiges Datumsformat');
            }
            
            if ($time && !preg_match('/^\d{2}:\d{2}$/', $time)) {
                throw new Exception('Ungültiges Zeitformat');
            }
            
            // Debug-Zeit setzen
            \Dionysosv2\Models\Settings::setDebugDateTime($date, $time);
            
            $response = [
                'success' => true,
                'message' => 'Debug-Zeit erfolgreich gesetzt',
                'debug_date' => $date,
                'debug_time' => $time
            ];
            break;
            
        case 'reset_debug_time':
            \Dionysosv2\Models\Settings::resetDebug();
            
            $response = [
                'success' => true,
                'message' => 'Debug-Zeit zurückgesetzt'
            ];
            break;
            
        case 'get_debug_info':
            // Dummy PDO für Settings (wird nur für Debug-Info benötigt)
            $pdo = new PDO('sqlite:' . __DIR__ . '/../../database.db');
            $settings = new \Dionysosv2\Models\Settings($pdo);
            
            $response = [
                'success' => true,
                'debug_info' => $settings->getDebugInfo()
            ];
            break;
            
        default:
            throw new Exception('Unbekannte Aktion: ' . $action);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
