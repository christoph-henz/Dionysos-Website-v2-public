<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$origin = $_SERVER['HTTP_HOST'] ?? 'localhost';
header('Access-Control-Allow-Origin: https://' . $origin);
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

try {
    ini_set('session.name', 'PHPSESSID');
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_secure', false);
    ini_set('session.cookie_httponly', false);
    ini_set('session.cookie_samesite', 'Lax');
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'error' => 'Nicht authentifiziert'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Ungültige JSON-Daten']);
        exit;
    }
    
    if (!isset($input['action']) || !isset($input['type']) || !isset($input['id']) || !isset($input['status'])) {
        echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
        exit;
    }
    
    $action = $input['action'];
    $type = $input['type'];
    $id = (int)$input['id'];
    $status = $input['status'];
    
    if ($action !== 'update_status' || $type !== 'reservation') {
        echo json_encode(['success' => false, 'error' => 'Ungültige Aktion oder Typ']);
        exit;
    }
    
    $isLocal = $_SERVER['HTTP_HOST'] === 'localhost';
    
    if ($isLocal) {
        $database = new PDO('sqlite:' . __DIR__ . '/../../database.db');
    } else {
        $database = new PDO(
            'mysql:host=db************.hosting-data.io;dbname=dbs**********;charset=utf8mb4',
            'dbu***********', 
            '*************************'
        );
    }
    
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $database->prepare("SELECT status FROM reservations WHERE id = ?");
    $stmt->execute([$id]);
    $currentReservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentReservation) {
        echo json_encode(['success' => false, 'error' => 'Reservierung nicht gefunden']);
        exit;
    }
    
    $oldStatus = $currentReservation['status'];
    
    // Status in Datenbank aktualisieren
    if ($isLocal) {
        // SQLite
        $stmt = $database->prepare("
            UPDATE reservations 
            SET status = ?, updated_at = datetime('now') 
            WHERE id = ?
        ");
    } else {
        // MySQL
        $stmt = $database->prepare("
            UPDATE reservations 
            SET status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
    }
    
    $success = $stmt->execute([$status, $id]);
    
    if ($success) {
        // Telegram-Nachricht aktualisieren (falls vorhanden)
        $telegramStatus = '';
        try {
            // TelegramBotService laden
            require_once __DIR__ . '/../../vendor/autoload.php';
            $telegramService = new \Dionysosv2\Services\TelegramBotService($database);
            
            // Prüfen ob es eine Telegram Message ID für diese Reservierung gibt
            $stmt = $database->prepare("SELECT telegram_message_id FROM reservations WHERE id = ?");
            $stmt->execute([$id]);
            $messageId = $stmt->fetchColumn();
            
            if ($messageId) {
                // Telegram-Nachricht aktualisieren basierend auf Status-Änderung
                // Nur für confirmed/rejected Status Telegram updaten, andere Status ignorieren
                if ($status === 'confirmed') {
                    $telegramUpdated = $telegramService->handleCallback('confirm_reservation_' . $id, $messageId);
                    $telegramStatus = $telegramUpdated ? 'Telegram-Nachricht als bestätigt aktualisiert' : 'Telegram-Nachricht konnte nicht aktualisiert werden';
                } elseif ($status === 'rejected') {
                    $telegramUpdated = $telegramService->handleCallback('reject_reservation_' . $id, $messageId);
                    $telegramStatus = $telegramUpdated ? 'Telegram-Nachricht als abgelehnt aktualisiert' : 'Telegram-Nachricht konnte nicht aktualisiert werden';
                } else {
                    // Für Status wie 'arrived', 'no_show' etc. keine Telegram-Aktualisierung
                    $telegramStatus = 'Status-Änderung (' . $status . ') erfordert keine Telegram-Aktualisierung';
                }
            } else {
                $telegramStatus = 'Keine Telegram-Nachricht vorhanden';
            }
        } catch (Exception $e) {
            $telegramStatus = 'Telegram-Update-Fehler: ' . $e->getMessage();
        }
        
        $emailStatus = '';
        
        if ($oldStatus === 'pending' && $status === 'confirmed') {
            try {
                require_once __DIR__ . '/../../vendor/autoload.php';
                $emailService = new \Dionysosv2\Services\EmailService($database);
                $emailSent = $emailService->sendReservationConfirmation($id);
                $emailStatus = $emailSent ? 'Bestätigungs-E-Mail erfolgreich gesendet' : 'E-Mail konnte nicht gesendet werden';
            } catch (Exception $e) {
                $emailStatus = 'E-Mail-Fehler: ' . $e->getMessage();
            }
        } elseif ($status === 'cancelled') {
            try {
                require_once __DIR__ . '/../../vendor/autoload.php';
                $emailService = new \Dionysosv2\Services\EmailService($database);
                $emailSent = $emailService->sendReservationCancellation($id);
                $emailStatus = $emailSent ? 'Stornierungs-E-Mail erfolgreich gesendet' : 'Stornierungs-E-Mail konnte nicht gesendet werden';
            } catch (Exception $e) {
                $emailStatus = 'Stornierungs-E-Mail-Fehler: ' . $e->getMessage();
            }
        } else {
            $emailStatus = 'Keine E-Mail erforderlich für diesen Status-Wechsel';
        }
        
        $response = [
            'success' => true, 
            'message' => 'Status erfolgreich aktualisiert',
            'old_status' => $oldStatus,
            'new_status' => $status,
            'email_status' => $emailStatus,
            'telegram_status' => $telegramStatus
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fehler beim Aktualisieren des Status']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Interner Serverfehler: ' . $e->getMessage()
    ]);
}
?>
