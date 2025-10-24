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
    
    if ($action !== 'update_status' || $type !== 'order') {
        echo json_encode(['success' => false, 'error' => 'Ungültige Aktion oder Typ']);
        exit;
    }
    
    $autoloaderPath = '../../vendor/autoload.php';
    if (!file_exists($autoloaderPath)) {
        echo json_encode(['success' => false, 'error' => 'Autoloader nicht gefunden']);
        exit;
    }
    
    require_once $autoloaderPath;
    
    $isLocal = $_SERVER['HTTP_HOST'] === 'localhost';
    
    try {
        if ($isLocal) {
            $dbPath = __DIR__ . '/../../database.db';
            
            if (!file_exists($dbPath)) {
                throw new Exception("Datenbankdatei nicht gefunden");
            }
            
            $database = new PDO('sqlite:' . $dbPath);
        } else {
            $database = new PDO(
                'mysql:host=db************.hosting-data.io;dbname=dbs**********;charset=utf8mb4',
                'dbu***********', 
                '*************************'
            );
        }
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
    } catch (Exception $dbException) {
        echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen: ' . $dbException->getMessage()]);
        exit;
    }
    
    if ($isLocal) {
        $stmt = $database->prepare("
            SELECT i.*, 
                   GROUP_CONCAT(
                       oi.quantity || '|' || a.name || '|' || 
                       COALESCE(oi.options_json, '[]') || '|' || 
                       oi.total_price, 
                       '||'
                   ) as items
            FROM invoice i
            LEFT JOIN order_item oi ON i.id = oi.invoice_id
            LEFT JOIN article a ON oi.article_id = a.id
            WHERE i.id = :id
            GROUP BY i.id
        ");
        $stmt->execute(['id' => $id]);
    } else {
        $stmt = $database->prepare("
            SELECT i.*, 
                   GROUP_CONCAT(
                       CONCAT(oi.quantity, '|', a.name, '|', 
                              COALESCE(oi.options_json, '[]'), '|', 
                              oi.total_price)
                       SEPARATOR '||'
                   ) as items
            FROM invoice i
            LEFT JOIN order_item oi ON i.id = oi.invoice_id
            LEFT JOIN article a ON oi.article_id = a.id
            WHERE i.id = :id
            GROUP BY i.id
        ");
        $stmt->execute(['id' => $id]);
    }
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode([
            'success' => false,
            'error' => "Bestellung #{$id} nicht gefunden"
        ]);
        exit;
    }
    
    if ($isLocal) {
        $stmt = $database->prepare("UPDATE invoice SET status = :status, updated_at = datetime('now') WHERE id = :id");
    } else {
        $stmt = $database->prepare("UPDATE invoice SET status = :status, updated_at = NOW() WHERE id = :id");
    }
    $success = $stmt->execute(['status' => $status, 'id' => $id]);

    // Bestätigungsmail versenden, wenn Bestellung bestätigt wird
    if ($success && $stmt->rowCount() > 0) {
        require_once __DIR__ . '/../../src/Services/EmailService.php';
        $emailService = new \Dionysosv2\Services\EmailService($database);
        if ($status === 'accepted') {
            $emailService->sendOrderConfirmation($id);
        } elseif ($status === 'cancelled') {
            $emailService->sendOrderRejected($id);
        } elseif ($status === 'finished') {
            $emailService->sendOrderFinished($id);
        }
    }
    
    if ($success && $stmt->rowCount() > 0) {
        $response = [
            'success' => true,
            'message' => "Bestellung #{$id} wurde auf '{$status}' gesetzt"
        ];
        
        try {
            $telegramController = new \Dionysosv2\Controller\TelegramController();
            
            $telegramMessage = $telegramController->formatOrderStatusUpdate($order, $status);
            
            if ($telegramMessage) {
                $telegramResult = $telegramController->sendOrderStatusUpdate($order['id'], $status, $telegramMessage);
                
                if ($telegramResult) {
                    $response['telegram_status'] = "✅ Telegram-Benachrichtigung gesendet";
                } else {
                    $response['telegram_status'] = "⚠️ Telegram-Benachrichtigung fehlgeschlagen";
                }
            }
        } catch (Exception $e) {
            $response['telegram_status'] = "⚠️ Telegram-Fehler: " . $e->getMessage();
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'error' => "Bestellung #{$id} nicht gefunden oder Status unverändert"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Interner Serverfehler: ' . $e->getMessage()
    ]);
}
?>
