<?php

namespace Dionysosv2\Services;

use PDO;
use Exception;

class EmailService
{
    /**
     * Sendet eine Bestellbestätigung an den Kunden
     */
    public function sendOrderConfirmation(int $invoiceId): bool
    {
        try {
            // Bestelldaten abrufen
            $order = $this->getOrderData($invoiceId);
            if (!$order) {
                throw new Exception("Bestellung mit ID $invoiceId nicht gefunden");
            }
            // E-Mail-Inhalt generieren
            $subject = "Bestellbestätigung - Restaurant Dionysos";
            $message = $this->generateOrderConfirmationEmail($order);
            // E-Mail senden
            return $this->sendEmail($order['email'], $subject, $message, $order['name']);
        } catch (Exception $e) {
            error_log("E-Mail-Versand fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Holt die Bestelldaten inkl. Artikel und Optionen
     */
    private function getOrderData(int $invoiceId): ?array
    {
        // Rechnung abrufen
        $stmt = $this->database->prepare("SELECT * FROM invoice WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) return null;
        // Artikel abrufen
        $stmt = $this->database->prepare("SELECT oi.*, a.name as article_name FROM order_item oi LEFT JOIN article a ON oi.article_id = a.id WHERE oi.invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $order['items'] = $items;
        return $order;
    }

    /**
     * Generiert den HTML-Inhalt für die Bestellbestätigung
     */
    private function generateOrderConfirmationEmail(array $order): string
    {
        $orderDate = date('d.m.Y H:i', strtotime($order['created_on']));
        $totalAmount = number_format($order['total_amount'], 2);
        $name = htmlspecialchars($order['name']);
        $address = !empty($order['street']) ? htmlspecialchars($order['street'] . ' ' . $order['number'] . ', ' . $order['postal_code'] . ' ' . $order['city']) : 'Abholung im Restaurant';
        $itemsHtml = '';
        foreach ($order['items'] as $item) {
            $optionsText = '';
            if (!empty($item['options_json']) && $item['options_json'] !== '[]') {
                $options = json_decode($item['options_json'], true);
                if (is_array($options) && count($options) > 0) {
                    $optionParts = [];
                    foreach ($options as $opt) {
                        $optText = htmlspecialchars($opt['name'] ?? '');
                        if (isset($opt['price']) && $opt['price'] > 0) {
                            $optText .= ' (+' . number_format($opt['price'], 2) . '€)';
                        }
                        $optionParts[] = $optText;
                    }
                    $optionsText = '<br><span style="font-size:0.95em;color:#666;">Optionen: ' . implode(', ', $optionParts) . '</span>';
                }
            }
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td style="padding:8px 0;">' . htmlspecialchars($item['quantity']) . 'x</td>';
            $itemsHtml .= '<td style="padding:8px 0;">' . htmlspecialchars($item['article_name']) . $optionsText . '</td>';
            $itemsHtml .= '<td style="padding:8px 0; text-align:right;">' . number_format($item['total_price'], 2) . '€</td>';
            $itemsHtml .= '</tr>';
        }
        return "
        <!DOCTYPE html>
        <html lang='de'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bestellbestätigung</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #43a047; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>Restaurant Dionysos</h1>
                <p style='margin: 5px 0 0 0; font-size: 16px;'>Der Grieche am Main</p>
            </div>
            <div style='background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
                <h2 style='color: #43a047; margin-top: 0;'>✅ Bestellung erhalten</h2>
                <p>Liebe/r {$name},</p>
                <p>vielen Dank für Ihre Bestellung! Wir haben Ihre Bestellung erhalten und bearbeiten sie umgehend.</p>
                <div style='background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>📋 Ihre Bestelldetails:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr><td style='padding:8px 0; font-weight:bold;'>Bestell-Nr.:</td><td style='padding:8px 0;'>#{$order['id']}</td></tr>
                        <tr><td style='padding:8px 0; font-weight:bold;'>Datum:</td><td style='padding:8px 0;'>{$orderDate}</td></tr>
                        <tr><td style='padding:8px 0; font-weight:bold;'>Name:</td><td style='padding:8px 0;'>{$name}</td></tr>
                        <tr><td style='padding:8px 0; font-weight:bold;'>Adresse:</td><td style='padding:8px 0;'>{$address}</td></tr>
                        <tr><td style='padding:8px 0; font-weight:bold;'>Gesamtsumme:</td><td style='padding:8px 0;'>{$totalAmount}€</td></tr>
                    </table>
                </div>
                <div style='background: #fff3e0; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #f57c00;'>🛒 Ihre Artikel:</h4>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <thead>
                            <tr>
                                <th style='text-align:left;padding:8px 0;'>Menge</th>
                                <th style='text-align:left;padding:8px 0;'>Artikel</th>
                                <th style='text-align:right;padding:8px 0;'>Preis</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                        </tbody>
                    </table>
                </div>
                " . (!empty($order['notes']) ? "<div style='background: #e3f2fd; padding: 15px; border-radius: 6px; margin: 20px 0;'><h4 style='margin-top: 0; color: #1976d2;'>📝 Ihre Anmerkungen:</h4><p style='margin-bottom: 0;'>" . htmlspecialchars($order['notes']) . "</p></div>" : "") . "
                <p>Wir melden uns bei Ihnen, sobald Ihre Bestellung zur Abholung oder Lieferung bereit ist.</p>
                <p style='margin: 30px 0 10px 0;'>Mit freundlichen Grüßen<br>Ihr Team vom Restaurant Dionysos</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <div style='font-size: 12px; color: #666; text-align: center;'>
                    <p>Restaurant Dionysos | Floßhafen 27 | 63739 Aschaffenburg</p>
                    <p>Tel: 06021 25779 | E-Mail: info@dionysos-aburg.de</p>
                </div>
            </div>
        </body>
        </html>";
    }
    private PDO $database;
    private bool $isLocal;

    public function __construct(PDO $database)
    {
        $this->database = $database;
        $this->isLocal = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] === 'localhost' : true;
        //$this->isLocal = false; // E-Mail-Schutz deaktiviert, echte Mails werden versendet
    }

    /**
     * Sendet eine Reservierungsbestätigungs-E-Mail
     */
    public function sendReservationConfirmation(int $reservationId): bool
    {
        try {
            // Reservierungsdaten abrufen
            $reservation = $this->getReservationData($reservationId);
            if (!$reservation) {
                throw new Exception("Reservierung mit ID $reservationId nicht gefunden");
            }

            // E-Mail-Inhalt generieren
            $subject = "Reservierungsbestätigung - Restaurant Dionysos";
            $message = $this->generateReservationConfirmationEmail($reservation);

            // E-Mail senden
            return $this->sendEmail($reservation['email'], $subject, $message, $reservation['first_name'] . ' ' . $reservation['last_name']);

        } catch (Exception $e) {
            error_log("E-Mail-Versand fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sendet eine Reservierungsstornierung-E-Mail
     */
    public function sendReservationCancellation(int $reservationId): bool
    {
        try {
            // Reservierungsdaten abrufen
            $reservation = $this->getReservationData($reservationId);
            if (!$reservation) {
                throw new Exception("Reservierung mit ID $reservationId nicht gefunden");
            }

            // E-Mail-Inhalt generieren
            $subject = "Reservierung storniert - Restaurant Dionysos";
            $message = $this->generateReservationCancellationEmail($reservation);

            // E-Mail senden
            return $this->sendEmail($reservation['email'], $subject, $message, $reservation['first_name'] . ' ' . $reservation['last_name']);

        } catch (Exception $e) {
            error_log("E-Mail-Versand fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sendet eine Fertigstellungs-Mail an den Kunden
     */
    public function sendOrderFinished(int $invoiceId): bool
    {
        try {
            $order = $this->getOrderData($invoiceId);
            if (!$order) {
                throw new Exception("Bestellung mit ID $invoiceId nicht gefunden");
            }
            $subject = "Bestellung fertiggestellt - Restaurant Dionysos";
            $message = $this->generateOrderFinishedEmail($order);
            return $this->sendEmail($order['email'], $subject, $message, $order['name']);
        } catch (Exception $e) {
            error_log("E-Mail-Versand fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sendet eine Ablehnungs-Mail an den Kunden (z.B. wegen Überlastung)
     */
    public function sendOrderRejected(int $invoiceId): bool
    {
        try {
            $order = $this->getOrderData($invoiceId);
            if (!$order) {
                throw new Exception("Bestellung mit ID $invoiceId nicht gefunden");
            }
            $subject = "Bestellung abgelehnt - Restaurant Dionysos";
            $message = $this->generateOrderRejectedEmail($order);
            return $this->sendEmail($order['email'], $subject, $message, $order['name']);
        } catch (Exception $e) {
            error_log("E-Mail-Versand fehlgeschlagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ruft Reservierungsdaten aus der Datenbank ab
     */
    private function getReservationData(int $reservationId): ?array
    {
        $stmt = $this->database->prepare("
            SELECT * FROM reservations 
            WHERE id = ?
        ");
        $stmt->execute([$reservationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Generiert den HTML-Inhalt für die Bestätigungs-E-Mail
     */
    private function generateReservationConfirmationEmail(array $reservation): string
    {
        $reservationDate = date('d.m.Y', strtotime($reservation['reservation_date']));
        $reservationTime = $reservation['reservation_time'];
        $guests = $reservation['guests'];
        $name = htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']);
        
        return "
        <!DOCTYPE html>
        <html lang='de'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Reservierungsbestätigung</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #ffab66; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>Restaurant Dionysos</h1>
                <p style='margin: 5px 0 0 0; font-size: 16px;'>Der Grieche am Main</p>
            </div>
            
            <div style='background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
                <h2 style='color: #ffab66; margin-top: 0;'>✅ Reservierung bestätigt</h2>
                
                <p>Liebe/r {$name},</p>
                
                <p>wir freuen uns, Ihnen mitteilen zu können, dass Ihre Reservierung <strong>bestätigt</strong> wurde!</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>📋 Ihre Reservierungsdetails:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 120px;'>Datum:</td>
                            <td style='padding: 8px 0;'>{$reservationDate}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Uhrzeit:</td>
                            <td style='padding: 8px 0;'>{$reservationTime} Uhr</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Personen:</td>
                            <td style='padding: 8px 0;'>{$guests}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Reservierungs-Nr.:</td>
                            <td style='padding: 8px 0;'>#{$reservation['id']}</td>
                        </tr>
                    </table>
                </div>
                
                " . (!empty($reservation['notes']) ? "
                <div style='background: #fff3e0; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #f57c00;'>📝 Ihre Anmerkungen:</h4>
                    <p style='margin-bottom: 0;'>" . htmlspecialchars($reservation['notes']) . "</p>
                </div>
                " : "") . "
                
                <div style='background: #e8f5e8; padding: 20px; border-radius: 6px; margin: 20px 0;'>
                    <h3 style='color: #2e7d32; margin-top: 0;'>📍 So finden Sie uns:</h3>
                    <p style='margin: 5px 0;'><strong>Restaurant Dionysos</strong></p>
                    <p style='margin: 5px 0;'>Floßhafen 27</p>
                    <p style='margin: 5px 0;'>63739 Aschaffenburg</p>
                    <p style='margin: 15px 0 5px 0;'><strong>Telefon:</strong> 06021 25779</p>
                    <p style='margin: 5px 0;'><strong>E-Mail:</strong> info@dionysos-aburg.de</p>
                </div>
                
                <div style='background: #fff3e0; padding: 15px; border-radius: 6px; margin: 20px 0;'>
                    <h4 style='margin-top: 0; color: #f57c00;'>ℹ️ Wichtige Hinweise:</h4>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>Bitte erscheinen Sie pünktlich zu Ihrer Reservierung</li>
                        <li>Bei Verspätung über 15 Minuten kontaktieren Sie uns bitte</li>
                        <li>Stornierungen sind bis 24 Stunden vorher kostenfrei möglich</li>
                        <li>Bei Fragen erreichen Sie uns unter 06021 25779</li>
                    </ul>
                </div>
                
                <p>Wir freuen uns sehr auf Ihren Besuch und wünschen Ihnen bereits jetzt einen wundervollen Abend bei uns!</p>
                
                <p style='margin: 30px 0 10px 0;'>Mit freundlichen Grüßen<br>
                Ihr Team vom Restaurant Dionysos</p>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                
                <div style='font-size: 12px; color: #666; text-align: center;'>
                    <p>Restaurant Dionysos | Floßhafen 27 | 63739 Aschaffenburg</p>
                    <p>Tel: 06021 25779 | E-Mail: info@dionysos-aburg.de</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Generiert den HTML-Inhalt für die Stornierung-E-Mail
     */
    private function generateReservationCancellationEmail(array $reservation): string
    {
        $reservationDate = date('d.m.Y', strtotime($reservation['reservation_date']));
        $reservationTime = $reservation['reservation_time'];
        $name = htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']);
        
        return "
        <!DOCTYPE html>
        <html lang='de'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Reservierung storniert</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #d32f2f; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>Restaurant Dionysos</h1>
                <p style='margin: 5px 0 0 0; font-size: 16px;'>Der Grieche am Main</p>
            </div>
            
            <div style='background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
                <h2 style='color: #d32f2f; margin-top: 0;'>❌ Reservierung storniert</h2>
                
                <p>Liebe/r {$name},</p>
                
                <p>hiermit bestätigen wir die <strong>Stornierung</strong> Ihrer Reservierung.</p>
                
                <div style='background: #ffebee; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #d32f2f;'>
                    <h3 style='color: #333; margin-top: 0;'>📋 Stornierte Reservierung:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 120px;'>Datum:</td>
                            <td style='padding: 8px 0;'>{$reservationDate}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Uhrzeit:</td>
                            <td style='padding: 8px 0;'>{$reservationTime} Uhr</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Reservierungs-Nr.:</td>
                            <td style='padding: 8px 0;'>#{$reservation['id']}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Wir bedauern, dass Sie nicht zu uns kommen können. Gerne können Sie jederzeit eine neue Reservierung vornehmen.</p>
                
                <div style='background: #e8f5e8; padding: 20px; border-radius: 6px; margin: 20px 0;'>
                    <h3 style='color: #2e7d32; margin-top: 0;'>🍽️ Neue Reservierung</h3>
                    <p>Sie können jederzeit online oder telefonisch eine neue Reservierung vornehmen:</p>
                    <p style='margin: 10px 0;'><strong>Online:</strong> Über unsere Website</p>
                    <p style='margin: 10px 0;'><strong>Telefon:</strong> 06021 25779</p>
                </div>
                
                <p>Wir hoffen, Sie bald bei uns begrüßen zu dürfen!</p>
                
                <p style='margin: 30px 0 10px 0;'>Mit freundlichen Grüßen<br>
                Ihr Team vom Restaurant Dionysos</p>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                
                <div style='font-size: 12px; color: #666; text-align: center;'>
                    <p>Restaurant Dionysos | Floßhafen 27 | 63739 Aschaffenburg</p>
                    <p>Tel: 06021 25779 | E-Mail: info@dionysos-aburg.de</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Generiert den HTML-Inhalt für die Fertigstellungs-Mail
     */
    private function generateOrderFinishedEmail(array $order): string
    {
        $orderDate = date('d.m.Y H:i', strtotime($order['created_on']));
        $name = htmlspecialchars($order['name']);
        return "
        <!DOCTYPE html>
        <html lang='de'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bestellung fertiggestellt</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #43a047; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>Restaurant Dionysos</h1>
                <p style='margin: 5px 0 0 0; font-size: 16px;'>Der Grieche am Main</p>
            </div>
            <div style='background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
                <h2 style='color: #43a047; margin-top: 0;'>🏁 Bestellung fertiggestellt</h2>
                <p>Liebe/r {$name},</p>
                <p>Ihre Bestellung vom {$orderDate} ist jetzt fertig und kann abgeholt werden bzw. wird geliefert.</p>
                <p style='margin: 30px 0 10px 0;'>Mit freundlichen Grüßen<br>Ihr Team vom Restaurant Dionysos</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <div style='font-size: 12px; color: #666; text-align: center;'>
                    <p>Restaurant Dionysos | Floßhafen 27 | 63739 Aschaffenburg</p>
                    <p>Tel: 06021 25779 | E-Mail: info@dionysos-aburg.de</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generiert den HTML-Inhalt für die Ablehnungs-Mail
     */
    private function generateOrderRejectedEmail(array $order): string
    {
        $orderDate = date('d.m.Y H:i', strtotime($order['created_on']));
        $name = htmlspecialchars($order['name']);
        return "
        <!DOCTYPE html>
        <html lang='de'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Bestellung abgelehnt</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #d32f2f; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>Restaurant Dionysos</h1>
                <p style='margin: 5px 0 0 0; font-size: 16px;'>Der Grieche am Main</p>
            </div>
            <div style='background: white; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;'>
                <h2 style='color: #d32f2f; margin-top: 0;'>❌ Bestellung abgelehnt</h2>
                <p>Liebe/r {$name},</p>
                <p>leider müssen wir Ihre Bestellung vom {$orderDate} ablehnen, da unsere Küche aktuell ausgelastet ist und wir keine weiteren Bestellungen annehmen können.</p>
                <p>Wir bitten um Ihr Verständnis und hoffen, Sie zu einem späteren Zeitpunkt wieder begrüßen zu dürfen.</p>
                <p>Bei Fragen erreichen Sie uns unter 06021 25779 oder info@dionysos-aburg.de.</p>
                <p style='margin: 30px 0 10px 0;'>Mit freundlichen Grüßen<br>Ihr Team vom Restaurant Dionysos</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                <div style='font-size: 12px; color: #666; text-align: center;'>
                    <p>Restaurant Dionysos | Floßhafen 27 | 63739 Aschaffenburg</p>
                    <p>Tel: 06021 25779 | E-Mail: info@dionysos-aburg.de</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Sendet eine E-Mail über PHPs mail() Funktion
     */
    private function sendEmail(string $to, string $subject, string $message, string $recipientName = ''): bool
    {
        try {
            // E-Mail-Header setzen
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: Restaurant Dionysos <info@dionysos-aburg.de>',
                'Reply-To: info@dionysos-aburg.de',
                'X-Mailer: PHP/' . phpversion()
            ];

            // Im lokalen Entwicklungsmodus: E-Mail-Inhalt loggen statt senden
            if ($this->isLocal) {
                error_log("=== E-MAIL SIMULATION (Localhost) ===");
                error_log("An: $to");
                error_log("Betreff: $subject");
                error_log("Inhalt: " . strip_tags($message));
                error_log("=== ENDE E-MAIL SIMULATION ===");
                return true; // Simuliere erfolgreichen Versand
            }

            // Produktionsmodus: Tatsächlich E-Mail senden
            $success = mail($to, $subject, $message, implode("\r\n", $headers));
            
            if ($success) {
                error_log("E-Mail erfolgreich gesendet an: $to (Betreff: $subject)");
            } else {
                error_log("E-Mail-Versand fehlgeschlagen an: $to (Betreff: $subject)");
            }
            
            return $success;

        } catch (Exception $e) {
            error_log("E-Mail-Versand Fehler: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Testet die E-Mail-Konfiguration
     */
    public function testEmailConfiguration(): array
    {
        $testEmail = "test@example.com";
        $testSubject = "Test E-Mail - Restaurant Dionysos";
        $testMessage = "<h1>Test E-Mail</h1><p>Dies ist eine Test-E-Mail zur Überprüfung der E-Mail-Konfiguration.</p>";
        
        $result = [
            'success' => false,
            'message' => '',
            'is_local' => $this->isLocal
        ];
        
        try {
            if ($this->isLocal) {
                $result['success'] = true;
                $result['message'] = 'E-Mail-Test im lokalen Modus erfolgreich (simuliert)';
            } else {
                $success = $this->sendEmail($testEmail, $testSubject, $testMessage);
                $result['success'] = $success;
                $result['message'] = $success ? 'Test-E-Mail erfolgreich gesendet' : 'Test-E-Mail fehlgeschlagen';
            }
        } catch (Exception $e) {
            $result['message'] = 'E-Mail-Test Fehler: ' . $e->getMessage();
        }
        
        return $result;
    }
}
