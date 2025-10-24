<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Dionysosv2\Services\TelegramBotService;

// CORS-Header für AJAX-Requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Für OPTIONS-Request (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Nur POST-Requests verarbeiten
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Datenbankverbindung - gleiche Logik wie andere APIs
    $isLocal = $_SERVER['HTTP_HOST'] === 'localhost';
    
    if ($isLocal) {
        // SQLite für lokale Entwicklung
        $pdo = new PDO('sqlite:' . __DIR__ . '/../../database.db');
    } else {
        // MySQL für Produktion
        $pdo = new PDO(
            'mysql:host=db************.hosting-data.io;dbname=dbs**********;charset=utf8mb4',
            'dbu***********', 
            '*************************'
        );
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Request-Body parsen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $action = $input['action'] ?? null;
    
    // Session prüfen für Debug-Funktionen
    session_start();
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Nicht authentifiziert'
        ]);
        exit;
    }
    
    switch ($action) {
        case 'send_test_message':
            sendTestMessage($pdo);
            break;
            
        case 'check_status':
            checkTelegramStatus($pdo);
            break;
            
        case 'webhook_info':
            getWebhookInfo($pdo);
            break;
            
        case 'get_logs':
            getTelegramLogs();
            break;
            
        case 'update_status':
            // Legacy Support für alte Status-Updates
            handleLegacyStatusUpdate($input, $pdo, $isLocal);
            break;
            
        default:
            throw new Exception('Unbekannte Aktion: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function sendTestMessage($pdo) {
    try {
        // Telegram Bot Service laden
        $telegramService = new TelegramBotService($pdo);
        
        $testMessage = "🧪 **Test-Nachricht**\n\n";
        $testMessage .= "Dies ist eine Test-Nachricht vom Dionysos Restaurant-System.\n";
        $testMessage .= "Gesendet am: " . date('d.m.Y H:i:s') . "\n\n";
        $testMessage .= "✅ Telegram Bot funktioniert korrekt!";
        
        $result = $telegramService->sendMessage($testMessage);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Test-Nachricht erfolgreich gesendet'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Fehler beim Senden der Test-Nachricht'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'TelegramBot-Fehler: ' . $e->getMessage()
        ]);
    }
}

function checkTelegramStatus($pdo) {
    try {
        // Settings laden
        $settings = new \Dionysosv2\Models\Settings($pdo);
        
        $botToken = $settings->get('telegram_bot_token');
        $chatId = $settings->get('telegram_chat_id');
        
        $status = [
            'bot_token_configured' => !empty($botToken),
            'chat_id_configured' => !empty($chatId),
            'connection_ok' => false
        ];
        
        // Verbindung testen wenn beide Werte vorhanden sind
        if (!empty($botToken) && !empty($chatId)) {
            $testUrl = "https://api.telegram.org/bot{$botToken}/getMe";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($testUrl, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                $status['connection_ok'] = isset($data['ok']) && $data['ok'] === true;
            }
        }
        
        echo json_encode([
            'success' => true,
            'status' => $status
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Status-Check Fehler: ' . $e->getMessage()
        ]);
    }
}

function getWebhookInfo($pdo) {
    try {
        $settings = new \Dionysosv2\Models\Settings($pdo);
        $botToken = $settings->get('telegram_bot_token');
        
        if (empty($botToken)) {
            echo json_encode([
                'success' => false,
                'error' => 'Bot Token nicht konfiguriert'
            ]);
            return;
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['ok']) && $data['ok'] === true) {
                echo json_encode([
                    'success' => true,
                    'webhook_info' => $data['result']
                ]);
                return;
            }
        }
        
        echo json_encode([
            'success' => false,
            'error' => 'Webhook-Info konnte nicht abgerufen werden'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Webhook-Info Fehler: ' . $e->getMessage()
        ]);
    }
}

function getTelegramLogs() {
    try {
        // Log-Datei lesen (falls vorhanden)
        $logFiles = [
            __DIR__ . '/../../logs/telegram.log',
            __DIR__ . '/../../telegram.log',
            '/tmp/telegram.log'
        ];
        
        $logs = '';
        foreach ($logFiles as $logFile) {
            if (file_exists($logFile)) {
                $logs = file_get_contents($logFile);
                break;
            }
        }
        
        if (empty($logs)) {
            // Fallback: Letzte 10 Telegram-Aktivitäten aus Datenbank
            $logs = "Keine Log-Datei gefunden.\n";
            $logs .= "Telegram-Logs werden normalerweise in telegram.log gespeichert.\n\n";
            $logs .= "Letzter Zugriff: " . date('Y-m-d H:i:s') . "\n";
        } else {
            // Nur die letzten 100 Zeilen anzeigen
            $lines = explode("\n", $logs);
            if (count($lines) > 100) {
                $lines = array_slice($lines, -100);
                $logs = implode("\n", $lines);
            }
        }
        
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Log-Lese-Fehler: ' . $e->getMessage()
        ]);
    }
}

function handleLegacyStatusUpdate($input, $pdo, $isLocal) {
    $type = $input['type'] ?? null;
    $id = $input['id'] ?? null;
    $status = $input['status'] ?? null;
    
    if (!$type || !$id || !$status) {
        throw new Exception('Missing required parameters: type, id, status');
    }
    
    if ($type === 'order') {
        // Bestellung-Status aktualisieren
        $stmt = $pdo->prepare("UPDATE invoice SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $id]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Bestellung #{$id} wurde auf '{$status}' gesetzt"
            ]);
        } else {
            throw new Exception("Bestellung #{$id} nicht gefunden oder Status unverändert");
        }
        
    } elseif ($type === 'reservation') {
        // Reservierung-Status aktualisieren
        if ($isLocal) {
            // SQLite
            $stmt = $pdo->prepare("UPDATE reservations SET status = ?, updated_at = datetime('now') WHERE id = ?");
        } else {
            // MySQL
            $stmt = $pdo->prepare("UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?");
        }
        $success = $stmt->execute([$status, $id]);
        
        if ($success && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Reservierung #{$id} wurde auf '{$status}' gesetzt"
            ]);
        } else {
            throw new Exception("Reservierung #{$id} nicht gefunden oder Status unverändert");
        }
        
    } else {
        throw new Exception('Invalid type. Must be "order" or "reservation"');
    }
}
