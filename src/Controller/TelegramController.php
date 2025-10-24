<?php

namespace Dionysosv2\Controller;

use Dionysosv2\Services\TelegramBotService;
use Dionysosv2\Views\Page;
use Exception;

class TelegramController extends Page {
    private $telegramService;
    private $authController;

    public function __construct() {
        parent::__construct(); // Initialisiert $_database und $isLocal
        $this->telegramService = new TelegramBotService($this->_database);
        $this->authController = new \Dionysosv2\Controller\AuthController();
    }

    /**
     * Prüft Authentifizierung für geschützte Endpunkte
     */
    private function requireAuth() {
        $this->authController->requireAuth();
    }

    /**
     * Behandelt eingehende Telegram Webhooks
     */
    public function handleWebhook() {
        // Webhook-Daten von Telegram lesen
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        // Log für Debugging
        error_log("Telegram Webhook erhalten: " . $input);
        
        if (!$update) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        // Callback Query (Button-Klicks) behandeln
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }

        // Erfolgreiche Antwort an Telegram
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
    }

    private function handleCallbackQuery($callbackQuery) {
        $callbackData = $callbackQuery['data'];
        $messageId = $callbackQuery['message']['message_id'];
        
        // Benutzerdaten aus dem Callback extrahieren
        $fromUser = isset($callbackQuery['from']) ? $callbackQuery['from'] : null;
        
        // Callback an TelegramBotService weiterleiten
        $success = $this->telegramService->handleCallback($callbackData, $messageId, $fromUser);
        
        if ($success) {
            error_log("Callback erfolgreich verarbeitet: {$callbackData}");
        } else {
            error_log("Fehler beim Verarbeiten des Callbacks: {$callbackData}");
        }
    }

    /**
     * API-Endpunkt zum manuellen Senden von Test-Nachrichten
     */
    public function sendTestMessage() {
        $this->requireAuth();
        $message = "🧪 *Test-Nachricht*\n\nDas ist eine Test-Nachricht vom Dionysos Bot!";
        
        // Einfache Test-Nachricht ohne Buttons
        $url = "https://api.telegram.org/bot" . $this->getBotToken() . "/sendMessage";
        
        $data = [
            'chat_id' => $this->getChatId(),
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        header('Content-Type: application/json');
        
        if ($httpCode === 200) {
            echo json_encode(['success' => true, 'message' => 'Test-Nachricht gesendet']);
        } else {
            echo json_encode(['success' => false, 'error' => $response]);
        }
    }

    /**
     * API-Endpunkt zum Setzen des Webhooks
     */
    public function setupWebhook() {
        $this->requireAuth();
        $baseUrl = $this->getBaseUrl();
        $webhookUrl = $baseUrl . '/telegram/webhook';
        
        header('Content-Type: application/json');
        
        // Prüfe, ob es sich um eine lokale URL handelt
        if (strpos($webhookUrl, 'localhost') !== false || strpos($webhookUrl, '127.0.0.1') !== false) {
            echo json_encode([
                'success' => false,
                'error' => 'Webhooks funktionieren nicht mit localhost URLs. Für lokale Tests verwenden Sie den Polling-Modus oder einen Tunnel-Service wie ngrok.',
                'webhook_url' => $webhookUrl,
                'info' => 'Lokale Entwicklung: Telegram Webhooks benötigen eine öffentlich erreichbare HTTPS-URL'
            ]);
            return;
        }
        
        // Prüfe, ob HTTPS verwendet wird
        if (strpos($webhookUrl, 'https://') !== 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Webhooks benötigen HTTPS. Ihre URL: ' . $webhookUrl,
                'webhook_url' => $webhookUrl,
                'info' => 'Telegram Webhooks funktionieren nur mit HTTPS-URLs'
            ]);
            return;
        }
        
        $result = $this->telegramService->setWebhook($webhookUrl);
        
        if ($result && isset($result['ok']) && $result['ok']) {
            echo json_encode([
                'success' => true, 
                'webhook_url' => $webhookUrl,
                'message' => 'Webhook erfolgreich gesetzt',
                'telegram_response' => $result
            ]);
        } else {
            $errorMsg = 'Unbekannter Fehler';
            if (isset($result['description'])) {
                $errorMsg = $result['description'];
            }
            
            echo json_encode([
                'success' => false, 
                'error' => 'Telegram API Fehler: ' . $errorMsg,
                'webhook_url' => $webhookUrl,
                'telegram_response' => $result
            ]);
        }
    }

    /**
     * Konfigurationsseite für Bot-Einstellungen
     */
    public function showConfig() {
        $this->requireAuth();
        $settings = $this->getSettings();
        
        echo "<!DOCTYPE html>";
        echo "<html lang='de'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Telegram Bot Konfiguration</title>";
        echo "<style>";
        echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }";
        echo ".form-group { margin-bottom: 20px; }";
        echo "label { display: block; margin-bottom: 5px; font-weight: bold; }";
        echo "input[type='text'] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }";
        echo "button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }";
        echo "button:hover { background: #005a87; }";
        echo ".status { padding: 10px; margin: 10px 0; border-radius: 4px; }";
        echo ".success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }";
        echo ".error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }";
        echo ".info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }";
        echo ".info-box { background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; margin: 15px 0; border-radius: 4px; }";
        echo ".warning-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 4px; }";
        echo ".warning-box h3 { color: #856404; }";
        echo ".info-box h3 { color: #155724; }";
        echo "</style>";
        echo "</head>";
        echo "<body>";
        
        echo "<h1>🤖 Telegram Bot Konfiguration</h1>";
        
        echo "<form method='post' action='/telegram/save-config'>";
        echo "<div class='form-group'>";
        echo "<label for='bot_token'>Bot Token:</label>";
        echo "<input type='text' id='bot_token' name='bot_token' value='" . htmlspecialchars($settings['telegram_bot_token'] ?? '') . "' placeholder='1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ'>";
        echo "<small>Erhalten Sie von @BotFather auf Telegram</small>";
        echo "</div>";
        
        echo "<div class='form-group'>";
        echo "<label for='chat_id'>Chat ID:</label>";
        echo "<input type='text' id='chat_id' name='chat_id' value='" . htmlspecialchars($settings['telegram_chat_id'] ?? '') . "' placeholder='-1001234567890'>";
        echo "<small>Ihre Telegram Chat-ID oder Gruppen-ID</small>";
        echo "</div>";
        
        echo "<button type='submit'>Einstellungen speichern</button>";
        echo "</form>";
        
        echo "<hr>";
        
        echo "<h2>🔧 Bot-Aktionen</h2>";
        echo "<button onclick='setupWebhook()'>Webhook einrichten</button>";
        echo "<button onclick='checkWebhook()'>Webhook-Status prüfen</button>";
        echo "<button onclick='removeWebhook()'>Webhook entfernen</button>";
        echo "<button onclick='sendTestMessage()'>Test-Nachricht senden</button>";
        
        echo "<div id='status'></div>";
        
        echo "<hr>";
        
        echo "<h2>📋 Anleitung</h2>";
        echo "<div class='info-box'>";
        echo "<h3>🚀 Für Live-Server (Produktion)</h3>";
        echo "<ol>";
        echo "<li><strong>Bot erstellen:</strong> Schreiben Sie @BotFather auf Telegram und erstellen Sie einen neuen Bot mit /newbot</li>";
        echo "<li><strong>Token erhalten:</strong> Kopieren Sie den Bot-Token von BotFather</li>";
        echo "<li><strong>Chat-ID finden:</strong> Fügen Sie den Bot zu Ihrem Chat hinzu und senden Sie eine Nachricht. Dann rufen Sie https://api.telegram.org/bot[BOT_TOKEN]/getUpdates auf</li>";
        echo "<li><strong>Konfiguration speichern:</strong> Tragen Sie Token und Chat-ID oben ein und speichern Sie</li>";
        echo "<li><strong>Webhook einrichten:</strong> Klicken Sie auf 'Webhook einrichten' (funktioniert nur auf HTTPS-Servern)</li>";
        echo "<li><strong>Testen:</strong> Senden Sie eine Test-Nachricht</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<div class='warning-box'>";
        echo "<h3>⚠️ Für lokale Entwicklung (localhost)</h3>";
        echo "<p><strong>Webhooks funktionieren nicht mit localhost!</strong> Telegram benötigt eine öffentlich erreichbare HTTPS-URL.</p>";
        echo "<p><strong>Alternativen für lokale Tests:</strong></p>";
        echo "<ul>";
        echo "<li><strong>ngrok verwenden:</strong> Installieren Sie ngrok und tunneln Sie localhost (https://ngrok.com/)</li>";
        echo "<li><strong>Polling-Modus:</strong> Verwenden Sie getUpdates statt Webhooks für lokale Tests</li>";
        echo "<li><strong>Live-Server testen:</strong> Deployen Sie auf einen HTTPS-Server für Webhook-Tests</li>";
        echo "</ul>";
        echo "<p>💡 <strong>Tipp:</strong> Sie können trotzdem Test-Nachrichten senden, um die Bot-Konfiguration zu prüfen!</p>";
        echo "</div>";
        
        echo "<script>";
        echo "function setupWebhook() {";
        echo "  fetch('/telegram/setup-webhook')";
        echo "    .then(response => response.json())";
        echo "    .then(data => {";
        echo "      const status = document.getElementById('status');";
        echo "      if (data.success) {";
        echo "        status.innerHTML = '<div class=\"status success\">Webhook erfolgreich eingerichtet: ' + data.webhook_url + '</div>';";
        echo "      } else {";
        echo "        status.innerHTML = '<div class=\"status error\">Fehler: ' + data.error + '</div>';";
        echo "      }";
        echo "    });";
        echo "}";
        
        echo "function sendTestMessage() {";
        echo "  fetch('/telegram/test')";
        echo "    .then(response => response.json())";
        echo "    .then(data => {";
        echo "      const status = document.getElementById('status');";
        echo "      if (data.success) {";
        echo "        status.innerHTML = '<div class=\"status success\">' + data.message + '</div>';";
        echo "      } else {";
        echo "        status.innerHTML = '<div class=\"status error\">Fehler: ' + data.error + '</div>';";
        echo "      }";
        echo "    });";
        echo "}";
        
        echo "function checkWebhook() {";
        echo "  fetch('/telegram/webhook-info')";
        echo "    .then(response => response.json())";
        echo "    .then(data => {";
        echo "      const status = document.getElementById('status');";
        echo "      if (data.success) {";
        echo "        const webhookUrl = data.webhook_info.url || 'Kein Webhook gesetzt';";
        echo "        status.innerHTML = '<div class=\"status info\"><strong>Webhook-Status:</strong><br>URL: ' + webhookUrl + '<br>Pending Updates: ' + (data.webhook_info.pending_update_count || 0) + '</div>';";
        echo "      } else {";
        echo "        status.innerHTML = '<div class=\"status error\">Fehler: ' + data.error + '</div>';";
        echo "      }";
        echo "    });";
        echo "}";
        
        echo "function removeWebhook() {";
        echo "  if (!confirm('Webhook wirklich entfernen?')) return;";
        echo "  fetch('/telegram/remove-webhook')";
        echo "    .then(response => response.json())";
        echo "    .then(data => {";
        echo "      const status = document.getElementById('status');";
        echo "      if (data.success) {";
        echo "        status.innerHTML = '<div class=\"status success\">Webhook erfolgreich entfernt</div>';";
        echo "      } else {";
        echo "        status.innerHTML = '<div class=\"status error\">Fehler: ' + data.error + '</div>';";
        echo "      }";
        echo "    });";
        echo "}";
        echo "</script>";
        
        echo "</body>";
        echo "</html>";
    }

    /**
     * Speichert Bot-Konfiguration
     */
    public function saveConfig() {
        $this->requireAuth();
        if ($_POST['bot_token'] && $_POST['chat_id']) {
            $this->saveSetting('telegram_bot_token', $_POST['bot_token']);
            $this->saveSetting('telegram_chat_id', $_POST['chat_id']);
            
            // Redirect zurück zur Konfigurationsseite
            header('Location: /telegram/config?saved=1');
            exit;
        } else {
            header('Location: /telegram/config?error=1');
            exit;
        }
    }

    private function getSettings() {
        $stmt = $this->_database->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    private function saveSetting($key, $value) {
        if ($this->isLocal) {
            $stmt = $this->_database->prepare("
                INSERT OR REPLACE INTO settings (setting_key, setting_value, category) 
                VALUES (?, ?, 'telegram')
            ");
        } else {
            $stmt = $this->_database->prepare("
                INSERT INTO settings (setting_key, setting_value, category) 
                VALUES (?, ?, 'telegram')
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
        }
        return $stmt->execute([$key, $value]);
    }

    private function getBotToken() {
        $stmt = $this->_database->prepare("SELECT setting_value FROM settings WHERE setting_key = 'telegram_bot_token'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function getChatId() {
        $stmt = $this->_database->prepare("SELECT setting_value FROM settings WHERE setting_key = 'telegram_chat_id'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    public function getWebhookInfo() {
        $this->requireAuth();
        $botToken = $this->getBotToken();
        if (!$botToken) {
            echo json_encode(['success' => false, 'error' => 'Bot Token nicht konfiguriert']);
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        header('Content-Type: application/json');
        
        if ($data && isset($data['ok']) && $data['ok']) {
            echo json_encode([
                'success' => true,
                'webhook_info' => $data['result']
            ]);
        } else {
            $errorMsg = isset($data['description']) ? $data['description'] : 'Unbekannter Fehler';
            echo json_encode([
                'success' => false,
                'error' => 'Telegram API Fehler: ' . $errorMsg
            ]);
        }
    }

    public function removeWebhook() {
        $this->requireAuth();
        $botToken = $this->getBotToken();
        if (!$botToken) {
            echo json_encode(['success' => false, 'error' => 'Bot Token nicht konfiguriert']);
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/deleteWebhook";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        header('Content-Type: application/json');
        
        if ($data && isset($data['ok']) && $data['ok']) {
            echo json_encode([
                'success' => true,
                'message' => 'Webhook erfolgreich entfernt'
            ]);
        } else {
            $errorMsg = isset($data['description']) ? $data['description'] : 'Unbekannter Fehler';
            echo json_encode([
                'success' => false,
                'error' => 'Telegram API Fehler: ' . $errorMsg
            ]);
        }
    }

    /**
     * Formatiert eine Bestellungs-Status-Update-Nachricht für Telegram
     */
    public function formatOrderStatusUpdate($order, $newStatus) {
        if (!$order) return null;
        
        $statusEmojis = [
            'pending' => '⏳',
            'accepted' => '✅', 
            'finished' => '🏁',
            'cancelled' => '❌'
        ];
        
        $statusTexts = [
            'pending' => 'Wartet auf Bestätigung',
            'accepted' => 'Angenommen - In Bearbeitung',
            'finished' => 'Fertiggestellt - Bereit zur Abholung/Lieferung',
            'cancelled' => 'Storniert'
        ];
        
        $emoji = $statusEmojis[$newStatus] ?? '📝';
        $statusText = $statusTexts[$newStatus] ?? $newStatus;
        
        $isDelivery = !empty($order['street']);
        $deliveryType = $isDelivery ? '🚚 Lieferung' : '🏃 Abholung';
        
        $message = "🍽️ *Bestellungs-Status Update*\n\n";
        $message .= "{$emoji} *Status:* {$statusText}\n";
        $message .= "📋 *Bestellung:* #{$order['id']}\n";
        $message .= "👤 *Kunde:* {$order['name']}\n";
        $message .= "📞 *Telefon:* {$order['phone']}\n";
        $message .= "🎯 *Art:* {$deliveryType}\n";
        
        if ($isDelivery) {
            $message .= "📍 *Adresse:* {$order['street']} {$order['number']}, {$order['postal_code']} {$order['city']}\n";
        }
        
        $message .= "💰 *Betrag:* " . number_format($order['total_amount'], 2) . "€\n";
        $message .= "⏰ *Bestellt:* " . date('d.m.Y H:i', strtotime($order['created_on'])) . "\n";
        $message .= "🕐 *Status-Update:* " . date('d.m.Y H:i:s') . "\n";
        
        // Artikel-Liste hinzufügen
        if (!empty($order['items'])) {
            $message .= "\n📝 *Artikel:*\n";
            $items = explode('||', $order['items']);
            foreach ($items as $item) {
                $item = trim($item);
                if (empty($item)) continue;
                
                $parts = explode('|', $item);
                if (count($parts) >= 4) {
                    $quantity = trim($parts[0]);
                    $articleName = trim($parts[1]);
                    $optionsJson = trim($parts[2]);
                    $price = trim($parts[3]);
                    
                    // Artikelzeile erstellen
                    $itemText = "• {$quantity}x {$articleName} ({$price}€)";
                    
                    // Optionen hinzufügen wenn vorhanden
                    if (!empty($optionsJson) && $optionsJson !== '[]') {
                        $options = json_decode($optionsJson, true);
                        if ($options && is_array($options)) {
                            foreach ($options as $option) {
                                if (isset($option['name'])) {
                                    $priceText = '';
                                    if (isset($option['price']) && $option['price'] != 0) {
                                        if ($option['price'] > 0) {
                                            $priceText = ' (+' . number_format($option['price'], 2) . '€)';
                                        } else {
                                            $priceText = ' (' . number_format($option['price'], 2) . '€)';
                                        }
                                    }
                                    $itemText .= "\n     → " . $option['name'] . $priceText;
                                }
                            }
                        }
                    }
                    
                    $message .= $itemText . "\n";
                }
            }
        }
        
        // Status-spezifische Hinweise
        switch ($newStatus) {
            case 'accepted':
                $message .= "\n🔄 *Die Bestellung wird nun zubereitet*";
                break;
            case 'finished':
                if ($isDelivery) {
                    $message .= "\n🚚 *Die Bestellung ist fertig und wird geliefert*";
                } else {
                    $message .= "\n🏃 *Die Bestellung ist fertig und kann abgeholt werden*";
                }
                break;
            case 'cancelled':
                $message .= "\n❌ *Die Bestellung wurde storniert*";
                break;
        }
        
        return $message;
    }

    /**
     * Sendet eine Bestellungs-Status-Update-Nachricht via Telegram
     */
    public function sendOrderStatusUpdate($orderId, $status, $message) {
        try {
            $result = $this->telegramService->sendOrUpdateOrderStatusMessage($orderId, $status, $message, 'Admin');
            
            if ($result !== false) {
                error_log("Telegram-Nachricht für Bestellung #{$orderId} erfolgreich gesendet/aktualisiert (Message ID: {$result})");
                return true;
            } else {
                error_log("Telegram-Fehler für Bestellung #{$orderId}: Nachricht konnte nicht gesendet/aktualisiert werden");
                return false;
            }
        } catch (Exception $e) {
            error_log("Exception beim Senden/Aktualisieren der Telegram-Nachricht für Bestellung #{$orderId}: " . $e->getMessage());
            return false;
        }
    }
}
