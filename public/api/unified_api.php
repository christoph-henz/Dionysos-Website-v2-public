<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Dionysosv2\Services\EmailService;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    $action = $data['action'] ?? '';
    $type = $data['type'] ?? '';

    if ($action === 'update_status' && $type === 'reservation') {
        handleReservationStatusUpdate($data);
    } elseif ($action === 'get_cart' || $action === 'add_item' || $action === 'remove_item' || $action === 'clear_cart') {
        handleCartActions($data);
    } else {
        throw new Exception('Unknown action or type');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleReservationStatusUpdate($data) {
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_secure', false);
    ini_set('session.cookie_httponly', true);
    ini_set('session.cookie_samesite', 'Lax');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'error' => 'Nicht authentifiziert'
        ]);
        return;
    }

    $reservationId = $data['id'] ?? null;
    $newStatus = $data['status'] ?? null;

    if (!$reservationId || !$newStatus) {
        throw new Exception('Fehlende Parameter: id oder status');
    }

    // Datenbankverbindung
    $configPath = __DIR__ . '/../../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception('Datenbankonfiguration nicht gefunden');
    }
    
    $config = require $configPath;
    $dsn = $config['dsn'];
    $username = $config['username'] ?? null;
    $password = $config['password'] ?? null;
    $options = $config['options'] ?? [];
    
    $pdo = new PDO($dsn, $username, $password, $options);

    // Reservierung aktualisieren und alte Daten für E-Mail laden
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        throw new Exception('Reservierung nicht gefunden');
    }

    // Status aktualisieren
    $updateStmt = $pdo->prepare("UPDATE reservations SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->execute([$newStatus, $reservationId]);

    $emailStatus = '';
    
    // E-Mail senden wenn Status confirmed oder rejected
    if ($newStatus === 'confirmed' || $newStatus === 'rejected') {
        try {
            $emailService = new EmailService($pdo);
            
            if ($newStatus === 'confirmed') {
                $emailResult = $emailService->sendReservationConfirmation($reservation);
            } else {
                $emailResult = $emailService->sendReservationCancellation($reservation);
            }
            
            $emailStatus = $emailResult ? 'E-Mail erfolgreich versendet' : 'E-Mail konnte nicht versendet werden';
        } catch (Exception $e) {
            $emailStatus = 'E-Mail Fehler: ' . $e->getMessage();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reservierungsstatus erfolgreich aktualisiert',
        'email_status' => $emailStatus,
        'reservation_id' => $reservationId,
        'new_status' => $newStatus
    ]);
}

function handleCartActions($data) {
    // Cart-Funktionalität hier implementieren
    // Vorläufig nur Platzhalter
    echo json_encode([
        'success' => true,
        'message' => 'Cart-Funktion noch nicht implementiert'
    ]);
}
?>
