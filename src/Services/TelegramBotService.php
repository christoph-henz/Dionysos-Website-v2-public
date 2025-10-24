<?php

namespace Dionysosv2\Services;

use PDO;
use DateTime;
use Exception;

class TelegramBotService {
    private $pdo;
    private $botToken;
    private $chatId;
    private $baseUrl;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        
        // Bot-Token und Chat-ID aus der Settings-Tabelle laden
        $this->loadSettings();
        
        // Webhook URL für Bestätigungen
        $this->baseUrl = $this->getBaseUrl();
    }

    private function loadSettings() {
        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $this->botToken = $settings['telegram_bot_token'] ?? null;
        $this->chatId = $settings['telegram_chat_id'] ?? null;
        
        if (!$this->botToken || !$this->chatId) {
            error_log("Telegram Bot: Token oder Chat-ID nicht konfiguriert");
        }
    }

    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Sendet eine neue Bestellung an Telegram
     */
    public function sendOrderNotification($invoiceId) {
        if (!$this->botToken || !$this->chatId) {
            error_log("Telegram Bot nicht konfiguriert für Invoice ID: " . $invoiceId);
            return false;
        }

        try {
            // Invoice-Daten laden
            $invoice = $this->getInvoiceData($invoiceId);
            if (!$invoice) {
                error_log("Invoice nicht gefunden: " . $invoiceId);
                return false;
            }

            // Nachricht formatieren
            $message = $this->formatOrderMessage($invoice);
            
            // Buttons je nach Status
            $buttons = [];
            if ($invoice['status'] === 'pending') {
                $buttons[] = ['text' => '✅ Bestätigen', 'callback_data' => "confirm_order_{$invoiceId}"];
                $buttons[] = ['text' => '❌ Ablehnen', 'callback_data' => "reject_order_{$invoiceId}"];
            } elseif ($invoice['status'] === 'accepted') {
                $buttons[] = ['text' => '🏁 Fertigstellen', 'callback_data' => "complete_order_{$invoiceId}"];
            }
            $keyboard = [
                'inline_keyboard' => [$buttons]
            ];

            // Nachricht senden
            $messageId = $this->sendMessage($message, $keyboard);
            
            if ($messageId) {
                // Message ID in Datenbank speichern
                $this->updateInvoiceTelegramMessageId($invoiceId, $messageId);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Fehler beim Senden der Bestellbenachrichtigung: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sendet eine neue Reservierung an Telegram
     */
    public function sendReservationNotification($reservationId) {
        if (!$this->botToken || !$this->chatId) {
            error_log("Telegram Bot nicht konfiguriert für Reservation ID: " . $reservationId);
            return false;
        }

        try {
            // Reservierung-Daten laden
            $reservation = $this->getReservationData($reservationId);
            if (!$reservation) {
                error_log("Reservierung nicht gefunden: " . $reservationId);
                return false;
            }

            // Nachricht formatieren
            $message = $this->formatReservationMessage($reservation);
            
            // Inline Keyboard für Bestätigung/Ablehnung
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Bestätigen', 'callback_data' => "confirm_reservation_{$reservationId}"],
                        ['text' => '❌ Ablehnen', 'callback_data' => "reject_reservation_{$reservationId}"]
                    ]
                ]
            ];

            // Nachricht senden
            $messageId = $this->sendMessage($message, $keyboard);
            
            if ($messageId) {
                // Message ID in Datenbank speichern
                $this->updateReservationTelegramMessageId($reservationId, $messageId);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Fehler beim Senden der Reservierungsbenachrichtigung: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Behandelt Telegram Webhook Callbacks
     */
    public function handleCallback($callbackData, $messageId, $fromUser = null) {
        $parts = explode('_', $callbackData);
        
        if (count($parts) < 3) {
            return false;
        }

    $action = $parts[0]; // confirm/reject/complete
    $type = $parts[1];   // order/reservation
    $id = $parts[2];     // ID

        // Benutzername aus Telegram-Daten extrahieren
        $editedBy = $this->extractUserName($fromUser);

        switch ($type) {
            case 'order':
                return $this->handleOrderCallback($action, $id, $messageId, $editedBy);
            case 'reservation':
                return $this->handleReservationCallback($action, $id, $messageId, $editedBy);
            default:
                return false;
        }
    }

    private function extractUserName($fromUser) {
        if (!$fromUser) {
            return null;
        }

        // Priorität: first_name + last_name, dann username, dann first_name
        if (isset($fromUser['first_name'])) {
            $name = $fromUser['first_name'];
            if (isset($fromUser['last_name'])) {
                $name .= ' ' . $fromUser['last_name'];
            }
            return $name;
        } elseif (isset($fromUser['username'])) {
            return '@' . $fromUser['username'];
        }

        return 'Telegram User';
    }

    private function handleOrderCallback($action, $invoiceId, $messageId, $editedBy = null) {
        // Status-Mapping für Konsistenz mit Adminboard und E-Mail-Logik
        if ($action === 'confirm') {
            $status = 'accepted';
            $statusText = 'angenommen';
            $statusEmoji = '✅';
        } elseif ($action === 'complete') {
            $status = 'finished';
            $statusText = 'fertiggestellt';
            $statusEmoji = '🏁';
        } else {
            $status = 'cancelled';
            $statusText = 'storniert';
            $statusEmoji = '❌';
        }
        
        // Status in Datenbank aktualisieren
        $stmt = $this->pdo->prepare("UPDATE invoice SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $invoiceId]);
        
        if ($success) {
            // Vollständige Bestelldaten laden
            $invoice = $this->getInvoiceData($invoiceId);
            if ($invoice) {
                // Buttons je nach Status
                $buttons = [];
                if ($invoice['status'] === 'pending') {
                    $buttons[] = ['text' => '✅ Bestätigen', 'callback_data' => "confirm_order_{$invoiceId}"];
                    $buttons[] = ['text' => '❌ Ablehnen', 'callback_data' => "reject_order_{$invoiceId}"];
                } elseif ($invoice['status'] === 'accepted') {
                    $buttons[] = ['text' => '🏁 Fertigstellen', 'callback_data' => "complete_order_{$invoiceId}"];
                }
                $keyboard = [
                    'inline_keyboard' => [$buttons]
                ];
                // Ursprüngliche Nachricht mit Status-Update formatieren
                $newText = $this->formatOrderMessageWithStatus($invoice, $status, $statusText, $statusEmoji, $editedBy);
                $this->editMessage($messageId, $newText, $keyboard);
            } else {
                // Fallback falls Daten nicht geladen werden können
                $newText = "🔄 Bestellung #{$invoiceId} wurde {$statusText}!\n\n" . 
                          "Status: " . strtoupper($status) . "\n" .
                          "Bearbeitet: " . date('d.m.Y H:i:s');
                if ($editedBy) {
                    $newText .= "\nBearbeitet von: " . $editedBy;
                }
                $this->editMessage($messageId, $newText);
            }
            
            // Optional: Bestätigungsmail an Kunden senden
            $this->notifyCustomerOrderStatus($invoiceId, $status);
            
            return true;
        }
        
        return false;
    }

    private function handleReservationCallback($action, $reservationId, $messageId, $editedBy = null) {
        $status = ($action === 'confirm') ? 'confirmed' : 'rejected';
        $statusText = ($action === 'confirm') ? 'bestätigt' : 'abgelehnt';
        $statusEmoji = ($action === 'confirm') ? '✅' : '❌';
        
        // Status in Datenbank aktualisieren
        $stmt = $this->pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $reservationId]);
        
        if ($success) {
            // Vollständige Reservierungsdaten laden
            $reservation = $this->getReservationData($reservationId);
            if ($reservation) {
                // Ursprüngliche Nachricht mit Status-Update formatieren
                $newText = $this->formatReservationMessageWithStatus($reservation, $status, $statusText, $statusEmoji, $editedBy);
                $this->editMessage($messageId, $newText);
            } else {
                // Fallback falls Daten nicht geladen werden können
                $newText = "🔄 Reservierung #{$reservationId} wurde {$statusText}!\n\n" . 
                          "Status: " . strtoupper($status) . "\n" .
                          "Bearbeitet: " . date('d.m.Y H:i:s');
                if ($editedBy) {
                    $newText .= "\nBearbeitet von: " . $editedBy;
                }
                $this->editMessage($messageId, $newText);
            }
            
            // E-Mail an Kunden senden
            $this->notifyCustomerReservationStatus($reservationId, $status);
            
            return true;
        }
        
        return false;
    }

    private function getInvoiceData($invoiceId) {
        // Zuerst die Invoice-Daten laden
        $stmt = $this->pdo->prepare("SELECT * FROM invoice WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            return null;
        }
        
        // Dann die Order Items mit Details laden
        $stmt = $this->pdo->prepare("
            SELECT oi.*, a.name as article_name 
            FROM order_item oi 
            LEFT JOIN article a ON oi.article_id = a.id 
            WHERE oi.invoice_id = ?
        ");
        $stmt->execute([$invoiceId]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Items formatieren
        $formattedItems = [];
        foreach ($orderItems as $item) {
            // Hauptartikel mit Menge und Preis
            $itemText = $item['quantity'] . 'x ' . $item['article_name'] . ' (' . number_format($item['total_price'], 2) . '€)';
            
            // Optionen einzeln auflisten wenn vorhanden
            if (!empty($item['options_json'])) {
                $options = json_decode($item['options_json'], true);
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
                            $itemText .= "\n   → " . $option['name'] . $priceText;
                        }
                    }
                }
            }
            
            $formattedItems[] = $itemText;
        }
        
        $invoice['items'] = implode("\n", $formattedItems);
        return $invoice;
    }

    private function getReservationData($reservationId) {
        $stmt = $this->pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function formatOrderMessage($invoice) {
        $date = new DateTime($invoice['created_on']);
        
        $message = "🍽️ *NEUE BESTELLUNG*\n\n";
        $message .= "📋 *Bestellung #" . $invoice['id'] . "*\n\n";
        $message .= "👤 *Kunde:* " . $invoice['name'] . "\n";
        $message .= "📧 *Email:* " . $invoice['email'] . "\n";
        $message .= "📞 *Telefon:* " . $invoice['phone'] . "\n\n";
        $message .= "💳 *Bezahlmethode:* " . ($invoice['payment_method'] ?? '') . "\n\n";
        
        if (!empty($invoice['street'])) {
            $message .= "🏠 *Lieferadresse:*\n";
            $message .= $invoice['street'] . " " . $invoice['number'] . "\n";
            $message .= $invoice['postal_code'] . " " . $invoice['city'] . "\n\n";
        } else {
            $message .= "🏃 *Abholung*\n\n";
        }
        
        $message .= "📦 *Bestellte Artikel:*\n";
        $message .= $invoice['items'] . "\n\n";
        
        if (!empty($invoice['notes'])) {
            $message .= "📝 *Anmerkungen:*\n" . $invoice['notes'] . "\n\n";
        }
        
        $message .= "💰 *Gesamtsumme:* " . number_format($invoice['total_amount'], 2) . "€\n\n";
        $message .= "🕐 *Bestellt am:* " . $date->format('d.m.Y H:i:s') . "\n\n";
        $message .= "❓ *Bitte bestätigen oder ablehnen*";
        
        return $message;
    }

    private function formatOrderMessageWithStatus($invoice, $status, $statusText, $statusEmoji, $editedBy = null) {
        $date = new DateTime($invoice['created_on']);
        
        $message = $statusEmoji . " *BESTELLUNG " . strtoupper($statusText) . "*\n\n";
        $message .= "📋 *Bestellung #" . $invoice['id'] . "*\n\n";
        $message .= "👤 *Kunde:* " . $invoice['name'] . "\n";
        $message .= "📧 *Email:* " . $invoice['email'] . "\n";
        $message .= "📞 *Telefon:* " . $invoice['phone'] . "\n\n";
        $message .= "💳 *Bezahlmethode:* " . ($invoice['payment_method'] ?? '') . "\n\n";
        
        if (!empty($invoice['street'])) {
            $message .= "🏠 *Lieferadresse:*\n";
            $message .= $invoice['street'] . " " . $invoice['number'] . "\n";
            $message .= $invoice['postal_code'] . " " . $invoice['city'] . "\n\n";
        } else {
            $message .= "🏃 *Abholung*\n\n";
        }
        
        $message .= "📦 *Bestellte Artikel:*\n";
        $message .= $invoice['items'] . "\n\n";
        
        if (!empty($invoice['notes'])) {
            $message .= "📝 *Anmerkungen:*\n" . $invoice['notes'] . "\n\n";
        }
        
        $message .= "💰 *Gesamtsumme:* " . number_format($invoice['total_amount'], 2) . "€\n\n";
        $message .= "🕐 *Bestellt am:* " . $date->format('d.m.Y H:i:s') . "\n";
        $message .= "🔄 *Status geändert:* " . date('d.m.Y H:i:s') . "\n";
        
        if ($editedBy) {
            $message .= "👤 *Bearbeitet von:* " . $editedBy . "\n";
        }
        
        $message .= "\n✅ *Status: " . strtoupper($status) . "*";
        
        return $message;
    }

    private function formatReservationMessage($reservation) {
        $date = new DateTime($reservation['reservation_date']);
        $createdDate = new DateTime($reservation['created_at']);
        
        $message = "🍽️ *NEUE RESERVIERUNG*\n\n";
        $message .= "📋 *Reservierung #" . $reservation['id'] . "*\n\n";
        $message .= "👤 *Gast:* " . $reservation['first_name'] . " " . $reservation['last_name'] . "\n";
        $message .= "📧 *Email:* " . $reservation['email'] . "\n";
        $message .= "📞 *Telefon:* " . $reservation['phone'] . "\n\n";
        $message .= "📅 *Datum:* " . $date->format('d.m.Y') . "\n";
        $message .= "🕐 *Uhrzeit:* " . $reservation['reservation_time'] . "\n";
        $message .= "👥 *Anzahl Gäste:* " . $reservation['guests'] . "\n\n";
        
        if (!empty($reservation['notes'])) {
            $message .= "📝 *Anmerkungen:*\n" . $reservation['notes'] . "\n\n";
        }
        
        $message .= "🕐 *Reserviert am:* " . $createdDate->format('d.m.Y H:i:s') . "\n\n";
        $message .= "❓ *Bitte bestätigen oder ablehnen*";
        
        return $message;
    }

    private function formatReservationMessageWithStatus($reservation, $status, $statusText, $statusEmoji, $editedBy = null) {
        $date = new DateTime($reservation['reservation_date']);
        $createdDate = new DateTime($reservation['created_at']);
        
        $message = $statusEmoji . " *RESERVIERUNG " . strtoupper($statusText) . "*\n\n";
        $message .= "📋 *Reservierung #" . $reservation['id'] . "*\n\n";
        $message .= "👤 *Gast:* " . $reservation['first_name'] . " " . $reservation['last_name'] . "\n";
        $message .= "📧 *Email:* " . $reservation['email'] . "\n";
        $message .= "📞 *Telefon:* " . $reservation['phone'] . "\n\n";
        $message .= "📅 *Datum:* " . $date->format('d.m.Y') . "\n";
        $message .= "🕐 *Uhrzeit:* " . $reservation['reservation_time'] . "\n";
        $message .= "👥 *Anzahl Gäste:* " . $reservation['guests'] . "\n\n";
        
        if (!empty($reservation['notes'])) {
            $message .= "📝 *Anmerkungen:*\n" . $reservation['notes'] . "\n\n";
        }
        
        $message .= "🕐 *Reserviert am:* " . $createdDate->format('d.m.Y H:i:s') . "\n";
        $message .= "🔄 *Status geändert:* " . date('d.m.Y H:i:s') . "\n";
        
        if ($editedBy) {
            $message .= "👤 *Bearbeitet von:* " . $editedBy . "\n";
        }
        
        $message .= "\n✅ *Status: " . strtoupper($status) . "*";
        
        return $message;
    }

    public function sendMessage($text, $keyboard = null) {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $data = [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];
        
        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }
        
        $response = $this->makeRequest($url, $data);
        
        if ($response && isset($response['result']['message_id'])) {
            return $response['result']['message_id'];
        }
        
        return false;
    }

    /**
     * Sendet eine Order-Status-Update-Nachricht oder editiert eine bestehende
     */
    public function sendOrUpdateOrderStatusMessage($orderId, $status, $message, $editedBy = 'Admin') {
        try {
            // Prüfen ob bereits eine Telegram-Nachricht für diese Bestellung existiert
            $stmt = $this->pdo->prepare("SELECT telegram_message_id FROM invoice WHERE id = ?");
            $stmt->execute([$orderId]);
            $existingMessageId = $stmt->fetchColumn();
            
            if ($existingMessageId) {
                // Bestehende Nachricht editieren - Nachricht um "Bearbeitet von" erweitern
                $enhancedMessage = $message;
                if ($editedBy) {
                    // Prüfen ob bereits "Bearbeitet von" in der Nachricht steht
                    if (strpos($enhancedMessage, 'Bearbeitet von:') === false) {
                        // Vor dem letzten Status-Teil einfügen
                        $lines = explode("\n", $enhancedMessage);
                        $lastLine = array_pop($lines);
                        $lines[] = "👤 *Bearbeitet von:* " . $editedBy;
                        $lines[] = "";
                        $lines[] = $lastLine;
                        $enhancedMessage = implode("\n", $lines);
                    }
                }
                
                error_log("Editing existing Telegram message {$existingMessageId} for Order #{$orderId}");
                $success = $this->editMessage($existingMessageId, $enhancedMessage);
                return $success ? $existingMessageId : false;
            } else {
                // Neue Nachricht senden
                error_log("Sending new Telegram message for Order #{$orderId}");
                $messageId = $this->sendMessage($message);
                
                if ($messageId) {
                    // Message ID in Datenbank speichern
                    $this->updateInvoiceTelegramMessageId($orderId, $messageId);
                    return $messageId;
                }
                
                return false;
            }
        } catch (Exception $e) {
            error_log("Fehler beim Senden/Editieren der Order-Status-Nachricht: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sendet eine Reservation-Status-Update-Nachricht oder editiert eine bestehende
     */
    public function sendOrUpdateReservationStatusMessage($reservationId, $status, $message, $editedBy = 'Admin') {
        try {
            // Prüfen ob bereits eine Telegram-Nachricht für diese Reservierung existiert
            $stmt = $this->pdo->prepare("SELECT telegram_message_id FROM reservations WHERE id = ?");
            $stmt->execute([$reservationId]);
            $existingMessageId = $stmt->fetchColumn();
            
            if ($existingMessageId) {
                // Bestehende Nachricht editieren - Nachricht um "Bearbeitet von" erweitern
                $enhancedMessage = $message;
                if ($editedBy) {
                    // Prüfen ob bereits "Bearbeitet von" in der Nachricht steht
                    if (strpos($enhancedMessage, 'Bearbeitet von:') === false) {
                        // Vor dem letzten Status-Teil einfügen
                        $lines = explode("\n", $enhancedMessage);
                        $lastLine = array_pop($lines);
                        $lines[] = "👤 *Bearbeitet von:* " . $editedBy;
                        $lines[] = "";
                        $lines[] = $lastLine;
                        $enhancedMessage = implode("\n", $lines);
                    }
                }
                
                error_log("Editing existing Telegram message {$existingMessageId} for Reservation #{$reservationId}");
                $success = $this->editMessage($existingMessageId, $enhancedMessage);
                return $success ? $existingMessageId : false;
            } else {
                // Neue Nachricht senden
                error_log("Sending new Telegram message for Reservation #{$reservationId}");
                $messageId = $this->sendMessage($message);
                
                if ($messageId) {
                    // Message ID in Datenbank speichern
                    $this->updateReservationTelegramMessageId($reservationId, $messageId);
                    return $messageId;
                }
                
                return false;
            }
        } catch (Exception $e) {
            error_log("Fehler beim Senden/Editieren der Reservation-Status-Nachricht: " . $e->getMessage());
            return false;
        }
    }

    private function editMessage($messageId, $text, $keyboard = null) {
        $url = "https://api.telegram.org/bot{$this->botToken}/editMessageText";
        $data = [
            'chat_id' => $this->chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];
        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }
        $response = $this->makeRequest($url, $data);
        if ($response && isset($response['ok']) && $response['ok']) {
            error_log("Telegram message {$messageId} successfully edited");
            return true;
        } else {
            $errorMsg = isset($response['description']) ? $response['description'] : 'Unknown error';
            error_log("Failed to edit Telegram message {$messageId}: " . $errorMsg);
            return false;
        }
    }

    private function makeRequest($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            error_log("Telegram API Fehler: HTTP {$httpCode} - {$response}");
            return false;
        }
    }

    private function updateInvoiceTelegramMessageId($invoiceId, $messageId) {
        $stmt = $this->pdo->prepare("UPDATE invoice SET telegram_message_id = ? WHERE id = ?");
        return $stmt->execute([$messageId, $invoiceId]);
    }

    private function updateReservationTelegramMessageId($reservationId, $messageId) {
        $stmt = $this->pdo->prepare("UPDATE reservations SET telegram_message_id = ? WHERE id = ?");
        return $stmt->execute([$messageId, $reservationId]);
    }

    private function notifyCustomerOrderStatus($invoiceId, $status) {
        // E-Mail-Service verwenden um Bestell-Status-E-Mail zu senden
        try {
            require_once __DIR__ . '/EmailService.php';
            $emailService = new \Dionysosv2\Services\EmailService($this->pdo);
            if ($status === 'accepted') {
                $emailService->sendOrderConfirmation($invoiceId);
                error_log("Bestellung #{$invoiceId} angenommen - Bestätigungs-E-Mail gesendet");
            } elseif ($status === 'finished') {
                $emailService->sendOrderFinished($invoiceId);
                error_log("Bestellung #{$invoiceId} fertiggestellt - Fertigstellungs-E-Mail gesendet");
            } elseif ($status === 'cancelled') {
                $emailService->sendOrderRejected($invoiceId);
                error_log("Bestellung #{$invoiceId} storniert - Stornierungs-E-Mail gesendet");
            }
        } catch (Exception $e) {
            error_log("E-Mail-Versand für Bestellung #{$invoiceId} fehlgeschlagen: " . $e->getMessage());
        }
    }

    private function notifyCustomerReservationStatus($reservationId, $status) {
        // E-Mail-Service verwenden um Reservierungs-Status-E-Mail zu senden
        try {
            require_once __DIR__ . '/EmailService.php';
            $emailService = new EmailService($this->pdo);
            
            if ($status === 'confirmed') {
                $emailService->sendReservationConfirmation($reservationId);
                error_log("Reservierung #{$reservationId} bestätigt - Bestätigungs-E-Mail gesendet");
            } elseif ($status === 'rejected') {
                $emailService->sendReservationCancellation($reservationId);
                error_log("Reservierung #{$reservationId} abgelehnt - Stornierung-E-Mail gesendet");
            }
        } catch (Exception $e) {
            error_log("E-Mail-Versand für Reservierung #{$reservationId} fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * Webhooks für Telegram Bot setzen
     */
    public function setWebhook($webhookUrl) {
        $url = "https://api.telegram.org/bot{$this->botToken}/setWebhook";
        
        $data = [
            'url' => $webhookUrl
        ];
        
        return $this->makeRequest($url, $data);
    }
}
