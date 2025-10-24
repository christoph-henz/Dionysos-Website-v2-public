<?php

namespace Dionysosv2\Views;
use Dionysosv2\Controller\CartController;
use Dionysosv2\Models\Invoice;
use Dionysosv2\Services\TelegramBotService;
use Exception;

class OrderSubmit extends Page
{
    /**
     * Properties
     */
    private CartController $cartController;
    private Invoice $invoice;
    private array $systemSettings;

    /**
     * Instantiates members (to be defined above).
     * Calls the constructor of the parent i.e. page class.
     * So, the database connection is established.
     * @throws Exception
     */
    protected function __construct()
    {
        $this->cartController = new CartController();
        parent::__construct();
        $this->loadSystemSettings();
    }

    /**
     * Lädt die Systemeinstellungen aus der Datenbank
     */
    private function loadSystemSettings(): void
    {
        $stmt = $this->_database->prepare("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key IN ('order_system', 'pickup_system', 'delivery_system', 'pickup_enabled', 'delivery_enabled', 'order_system_enabled')
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $this->systemSettings = [
            'order_system' => ($settings['order_system'] ?? $settings['order_system_enabled'] ?? '1') === '1',
            'pickup_system' => ($settings['pickup_system'] ?? $settings['pickup_enabled'] ?? '1') === '1',
            'delivery_system' => ($settings['delivery_system'] ?? $settings['delivery_enabled'] ?? '0') === '1'
        ];
    }

    /**
     * Cleans up whatever is needed.
     * Calls the destructor of the parent i.e. page class.
     * So, the database connection is closed.
     */
    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * This main-function has the only purpose to create an instance
     * of the class and to get all the things going.
     * I.e. the operations of the class are called to produce
     * the output of the HTML-file.
     * The name "main" is no keyword for php. It is just used to
     * indicate that function as the central starting point.
     * To make it simpler this is a static function. That is you can simply
     * call it without first creating an instance of the class.
     * @return void
     */
    public static function main():void
    {
        // Output buffering starten um sicherzustellen, dass Weiterleitungen funktionieren
        if (!ob_get_level()) {
            ob_start();
        }
        
        // Generiere ein zufälliges Token und speichere es in der Sitzung
        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
        try {
            $page = new OrderSubmit();
            
            // Verarbeite POST-Daten VOR der View-Generierung
            $page->processReceivedData();
            
            // Nur View generieren wenn keine Weiterleitung stattgefunden hat
            $page->generateView();
        } catch (Exception $e) {
            //header("Content-type: text/plain; charset=UTF-8");
            header("Content-type: text/html; charset=UTF-8");
            echo $e->getMessage();
        }

        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
        
        // Output buffer leeren falls noch aktiv
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    /**
     * Processes the data that comes via GET or POST.
     * If this page is supposed to do something with submitted
     * data do it here.
     * @return void
     */
    protected function processReceivedData():void
    {
        parent::processReceivedData();
        // to do: call processReceivedData() for all members

        // Prüfe, ob es sich um eine Bestellungsübermittlung handelt
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_order'])) {
            // Wichtig: Bestellung verarbeiten BEVOR irgendwelche Ausgabe stattfindet
            $this->handleOrderSubmission();
        }
    }

    /**
     * Behandelt die Bestellungsübermittlung
     */
    private function handleOrderSubmission(): void
    {
        try {
            // CSRF-Token prüfen
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['token'] ?? '')) {
                throw new Exception('Ungültiges Token. Bitte laden Sie die Seite neu.');
            }

            // Warenkorb aus Session laden
            $cartData = $_SESSION['cart'] ?? null;
            
            // Prüfe ob Warenkorb existiert und konvertiere zu Array wenn nötig
            if ($cartData instanceof \Dionysosv2\Models\Cart) {
                // Cart-Objekt zu Array konvertieren
                $cart = $this->cartController->getCartItems();
            } elseif (is_array($cartData)) {
                // Bereits ein Array
                $cart = $cartData;
            } else {
                // Kein gültiger Warenkorb
                $cart = [];
            }
            
            if (empty($cart)) {
                throw new Exception("Warenkorb ist leer");
            }

            // Kundendaten validieren
            $customerData = $this->validateCustomerData();
            
            // Bestellung in Datenbank speichern
            $invoiceId = $this->saveOrder($customerData, $cart);
            
            if ($invoiceId) {
                // Telegram-Benachrichtigung senden
                $this->sendTelegramNotification($invoiceId);
                
                // Warenkorb leeren - prüfe ob es ein Cart-Objekt oder Array ist
                if (isset($_SESSION['cart'])) {
                    if ($_SESSION['cart'] instanceof \Dionysosv2\Models\Cart) {
                        // Neues leeres Cart-Objekt erstellen
                        $_SESSION['cart'] = new \Dionysosv2\Models\Cart();
                    } else {
                        // Array leeren
                        unset($_SESSION['cart']);
                    }
                }
                
                // Erfolgsmeldung setzen
                $_SESSION['order_success'] = true;
                $_SESSION['order_id'] = $invoiceId;
                
                // Sicherstellen, dass keine Ausgabe vor header() gesendet wurde
                if (!headers_sent()) {
                    // Weiterleitung zur Bestätigungsseite
                    header('Location: /order/success');
                    exit;
                } else {
                    // Fallback: Da bereits Output gesendet wurde, verwende Meta-Refresh
                    echo '<!DOCTYPE html><html><head>';
                    echo '<meta http-equiv="refresh" content="0;url=/order/success">';
                    echo '<title>Weiterleitung...</title></head><body>';
                    echo '<p>Weiterleitung zur Bestätigungsseite...</p>';
                    echo '<script>window.location.href = "/order/success";</script>';
                    echo '</body></html>';
                    exit;
                }
            } else {
                throw new Exception("Fehler beim Speichern der Bestellung");
            }
            
        } catch (Exception $e) {
            $_SESSION['order_error'] = $e->getMessage();
        }
    }

    /**
     * Validiert die Kundendaten
     */
    private function validateCustomerData(): array
    {
    $required = ['name', 'email', 'phone', 'paymentMethod'];
        $data = [];
        
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Feld '{$field}' ist erforderlich");
            }
            $data[$field] = trim($_POST[$field]);
        }
        
        // E-Mail validieren
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Ungültige E-Mail-Adresse");
        }
        
        // Lieferung oder Abholung
        $isDelivery = isset($_POST['orderType']) && $_POST['orderType'] === 'delivery';
        
        if ($isDelivery) {
            $addressFields = ['street', 'houseNumber', 'postalCode', 'city'];
            foreach ($addressFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Adressfeld '{$field}' ist für Lieferung erforderlich");
                }
                $data[$field] = trim($_POST[$field]);
            }
        } else {
            // Für Abholung leere Adresse setzen
            $data['street'] = '';
            $data['houseNumber'] = '';
            $data['postalCode'] = '';
            $data['city'] = '';
        }
        
        // Notizen hinzufügen (optional)
        $data['notes'] = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        return $data;
    }

    /**
     * Speichert die Bestellung in der Datenbank
     */
    private function saveOrder(array $customerData, array $cart): ?int
    {
        try {
            $this->_database->beginTransaction();
            
            // Invoice erstellen
            $stmt = $this->_database->prepare("
                INSERT INTO invoice (name, street, number, postal_code, city, email, phone, notes, payment_method, created_on, total_amount, tax_amount, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $totalAmount = $this->calculateCartTotal($cart);
            $taxAmount = $totalAmount * 0.19; // 19% MwSt

            $stmt->execute([
                $customerData['name'],
                $customerData['street'],
                $customerData['houseNumber'],
                $customerData['postalCode'],
                $customerData['city'],
                $customerData['email'],
                $customerData['phone'],
                $customerData['notes'],
                $customerData['paymentMethod'],
                date('Y-m-d H:i:s'),
                $totalAmount,
                $taxAmount
            ]);
            
            $invoiceId = $this->_database->lastInsertId();
            
            // Order Items hinzufügen
            foreach ($cart as $item) {
                $stmt = $this->_database->prepare("
                    INSERT INTO order_item (invoice_id, article_id, quantity, total_price, options_json) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                // Berechne korrekten Gesamtpreis für dieses Item
                $itemTotalPrice = isset($item['total']) ? $item['total'] : $item['price'] * $item['quantity'];
                
                // Optionen als JSON speichern
                $optionsJson = isset($item['options']) ? json_encode($item['options']) : null;
                
                $stmt->execute([
                    $invoiceId,
                    $item['id'],
                    $item['quantity'],
                    $itemTotalPrice,
                    $optionsJson
                ]);
            }
            
            $this->_database->commit();
            return $invoiceId;
            
        } catch (Exception $e) {
            $this->_database->rollBack();
            error_log("Fehler beim Speichern der Bestellung: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Berechnet die Gesamtsumme des Warenkorbs
     */
    private function calculateCartTotal(array $cart): float
    {
        $total = 0;
        foreach ($cart as $item) {
            // Verwende 'total' wenn verfügbar (enthält bereits Optionen), sonst berechne selbst
            if (isset($item['total'])) {
                $total += $item['total'];
            } else {
                // Fallback: Basis-Berechnung
                $itemTotal = $item['price'] * $item['quantity'];
                
                // Füge Optionspreise hinzu wenn vorhanden
                if (isset($item['options']) && is_array($item['options'])) {
                    foreach ($item['options'] as $option) {
                        if (isset($option['price'])) {
                            $itemTotal += $option['price'] * $item['quantity'];
                        }
                    }
                }
                
                $total += $itemTotal;
            }
        }
        return $total;
    }

    /**
     * Sendet Telegram-Benachrichtigung über neue Bestellung
     */
    private function sendTelegramNotification(int $invoiceId): void
    {
        try {
            $telegramService = new TelegramBotService($this->_database);
            $telegramService->sendOrderNotification($invoiceId);
        } catch (Exception $e) {
            // Telegram-Fehler sollten die Bestellung nicht verhindern
            error_log("Telegram-Benachrichtigung fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * First the required data is fetched and then the HTML is
     * assembled for output. i.e. the header is generated, the content
     * of the page ("view") is inserted and -if available- the content of
     * all views contained is generated.
     * Finally, the footer is added.
     * @return void
     */
    protected function generateView():void
    {
        // Prüfe ob das Bestellsystem aktiviert ist
        if (!$this->systemSettings['order_system']) {
            $this->generatePageHeader('Bestellsystem nicht verfügbar');
            $this->generateOrderSystemDisabledView();
            $this->generatePageFooter();
            return;
        }

        $this->generatePageHeader('Bestellung'); //to do: set optional parameters

        //$this->generateNav();
        $this->generateMainBody();
        $this->generatePageFooter();
    }

    /**
     * Generiert die Standby-UI wenn das Bestellsystem deaktiviert ist
     */
    private function generateOrderSystemDisabledView(): void
    {
        $this->generateIntroDisplay();
        
        echo '<div class="main-container">';
        echo '<div class="system-disabled-container">';
        echo '<div class="system-disabled-content">';
        echo '<div class="disabled-icon">🚫</div>';
        echo '<h1>Bestellsystem momentan nicht verfügbar</h1>';
        echo '<p>Unser Online-Bestellsystem ist derzeit deaktiviert. Wir bitten um Ihr Verständnis.</p>';
        echo '<div class="alternative-options">';
        echo '<h3>Alternative Bestellmöglichkeiten:</h3>';
        echo '<div class="contact-option">';
        echo '<div class="contact-icon">📞</div>';
        echo '<div class="contact-info">';
        echo '<strong>Telefonisch bestellen</strong>';
        echo '<p>06021 25779</p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="contact-option">';
        echo '<div class="contact-icon">🏃</div>';
        echo '<div class="contact-info">';
        echo '<strong>Direkt im Restaurant</strong>';
        echo '<p>Floßhafen 27, 63739 Aschaffenburg</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="action-buttons">';
        echo '<a href="/" class="btn btn-primary">Zurück zur Startseite</a>';
        echo '<a href="/reservation" class="btn btn-secondary">Tisch reservieren</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        $this->generateSystemDisabledStyles();
    }

    /**
     * Generiert CSS-Styles für die Standby-UIs
     */
    private function generateSystemDisabledStyles(): void
    {
        echo <<<'HTML'
        <style>
            .system-disabled-container {
                min-height: 80vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }

            .system-disabled-content {
                max-width: 600px;
                text-align: center;
                background: white;
                padding: 3rem;
                border-radius: 12px;
                box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            }

            .disabled-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }

            .system-disabled-content h1 {
                color: #d32f2f;
                margin-bottom: 1rem;
            }

            .system-disabled-content p {
                color: #666;
                margin-bottom: 2rem;
                font-size: 1.1rem;
            }

            .alternative-options {
                margin: 2rem 0;
                text-align: left;
            }

            .alternative-options h3 {
                color: #333;
                margin-bottom: 1rem;
                text-align: center;
            }

            .contact-option {
                display: flex;
                align-items: center;
                margin: 1rem 0;
                padding: 1rem;
                background: #f8f9fa;
                border-radius: 8px;
                gap: 1rem;
            }

            .contact-icon {
                font-size: 2rem;
                min-width: 50px;
                text-align: center;
            }

            .contact-info strong {
                display: block;
                color: #333;
                margin-bottom: 0.25rem;
            }

            .contact-info p {
                margin: 0;
                color: #666;
                font-size: 0.9rem;
            }

            .action-buttons {
                margin-top: 2rem;
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .btn {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                border-radius: 6px;
                text-decoration: none;
                font-weight: bold;
                transition: background-color 0.2s;
            }

            .btn-primary {
                background: #ffab66;
                color: white;
            }

            .btn-primary:hover {
                background: #ff9240;
            }

            .btn-secondary {
                background: #f5f5f5;
                color: #333;
                border: 1px solid #ddd;
            }

            .btn-secondary:hover {
                background: #e0e0e0;
            }

            @media (max-width: 768px) {
                .system-disabled-container {
                    padding: 1rem;
                }

                .system-disabled-content {
                    padding: 2rem;
                }

                .contact-option {
                    flex-direction: column;
                    text-align: center;
                }

                .action-buttons {
                    flex-direction: column;
                    align-items: center;
                }

                .btn {
                    width: 200px;
                    text-align: center;
                }
            }
        </style>
        HTML;
    }

    private function generateMainBody(){
        $this->generateIntroDisplay();
        $this->generateBody();
    }

    protected function additionalMetaData(): void
    {
        //Links for css or js
        echo <<< HTML
            <link rel="stylesheet" type="text/css" href="../public/assets/css/home.css"/>
            <link rel="stylesheet" type="text/css" href="../public/assets/css/order-menu.css"/>
            <script src="../public/assets/js/home-behavior.js"></script> 
        HTML;
    }

    private function generateIntroDisplay() : void{
        echo <<< HTML
            <!-- Sticky Header -->
            <header class="sticky-header">
                <nav class="menu">  
                    <a href="/">Startseite</a>
                    <a href="/reservation">Reservierung</a>
                    <a href="/#openings">Öffnungszeiten</a>
                </nav>
                <div class="logo">
                    <img src="../public/assets/img/logo.png" alt="LOGO"/>
                </div>
                <nav class="menu">
                    <a href="/order">Bestellen</a>
                    <a href="/#gallery">Gallerie</a>
                    <a href="/#contact">Kontakt</a>
                </nav>
            </header>
            <div class="intro-container">
              <!-- Oberer Header -->
              <div class="top-header">
                <div class="social-icons">
                  <a href="tel:0602125779"><img src="../public/assets/img/phone.svg" style="filter: invert(1);" alt="Phone" />06021 25779</a>
                  <a href="mailto:info@dionysos-aburg.de"><img src="../public/assets/img/mail.svg" alt="Mail" />info@dionysos-aburg.de</a>
                  <a href="https://www.instagram.com/dionysos_aburg/?hl=de" target="_blank"><img src="../public/assets/img/instagram.svg" alt="Instagram" />Instagram</a>
                </div>
              </div>
            
              <!-- Unterer Header -->
              <div class="main-header">
                <div class="menu-toggle">&#9776;</div> <!-- Drei-Punkte-Menü (Hamburger) -->
                <!-- Menü für mobile Ansicht -->
                  <nav class="menu" id="mobile-menu">
                    <a href="/">Startseite</a>
                    <a href="/reservation">Reservierung</a>
                    <a href="#openings">Öffnungszeiten</a>
                    <a href="/order">Bestellen</a>
                    <a href="#gallery">Gallerie</a>
                    <a href="#contact">Kontakt</a>
                  </nav>
                <nav class="menu">  
                  <a href="/">Startseite</a>
                  <a href="/reservation">Reservierung</a>
                  <a href="/#openings">Öffnungszeiten</a>
                </nav>
                <div class="logo"><img src="../public/assets/img/logo.png" alt="LOGO"/></div>
                <nav class="menu">
                  <a href="/order">Bestellen</a>
                  <a href="/#gallery">Gallerie</a>
                  <a href="/#contact">Kontakt</a>
                </nav>
                </div>
        HTML;
        echo '</div></div>';
    }

    private function generateBody()
    {
        $orderItems = $this->cartController->getCartItems();
        echo '<div class="main-container">';
        echo '<div class="order-grid">';
        
        // Left column - Order form
        echo '<div class="order-form-container">';
        // Error or Success Message Display
        if (isset($_SESSION['order_error'])) {
            echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['order_error']) . '</div>';
            unset($_SESSION['order_error']);
        }
        
        echo '<div class="order-form">';
        echo '<h2>Bestellinformationen</h2>';
        echo '<form id="orderForm" method="post" action="/order/submit">';
        echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['token'] . '">';
        echo '<input type="hidden" name="submit_order" value="1">';
    // Zahlungsmethode Pflichtfeld
    echo '<div class="form-group">';
    echo '<label for="paymentMethod">Bevorzugte Zahlungsmethode:*</label>';
    echo '<select id="paymentMethod" name="paymentMethod" required onchange="togglePaymentHint()">';
    echo '<option value="">Bitte wählen</option>';
    echo '<option value="Bar">Barzahlung</option>';
    echo '<option value="EC-Karte">EC-Karte</option>';
    echo '</select>';
    echo '<div id="ecHint" style="display:none;margin-top:8px;padding:8px;background:#fffbe6;border:1px solid #ffe58f;color:#ad8b00;border-radius:6px;font-size:1em;">';
    echo '⚠️ <strong>Hinweis:</strong> Es werden <b>nur EC-Karten</b> akzeptiert. <br>Keine Kreditkarten, Visa, Debitkarten oder andere Karten!<br><br>Bitte überprüfen Sie vorab, ob Ihre Karte eine EC-Karte ist und akzeptiert wird.';
    echo '</div>';
    echo '</div>';
    echo '<script>
    function togglePaymentHint() {
        var select = document.getElementById("paymentMethod");
        var hint = document.getElementById("ecHint");
        if (select.value === "EC-Karte") {
            hint.style.display = "block";
        } else {
            hint.style.display = "none";
        }
    }
    document.addEventListener("DOMContentLoaded", function() {
        togglePaymentHint();
        document.getElementById("paymentMethod").addEventListener("change", togglePaymentHint);
    });
    </script>';
        
        // Dynamisches Dropdown für Bestellart
        echo '<div class="form-group">';
        echo '<label for="orderType">Bestellart:</label>';
        echo '<select id="orderType" name="orderType" required onchange="toggleDeliveryFields()">';
        echo '<option value="">Bitte wählen</option>';
        
        if ($this->systemSettings['pickup_system']) {
            echo '<option value="pickup">Abholung</option>';
        }
        
        if ($this->systemSettings['delivery_system']) {
            echo '<option value="delivery">Lieferung</option>';
        }
        
        // Falls beide Systeme deaktiviert sind
        if (!$this->systemSettings['pickup_system'] && !$this->systemSettings['delivery_system']) {
            echo '<option value="" disabled>Keine Optionen verfügbar</option>';
        }
        
        echo '</select>';
        echo '</div>';

        echo <<<'HTML'
                <div class="form-group">
                    <label for="name">Name:*</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">E-Mail:*</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Telefon:*</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div id="deliveryFields" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="street">Straße:*</label>
                            <input type="text" id="street" name="street">
                        </div>
                        <div class="form-group">
                            <label for="houseNumber">Hausnummer:*</label>
                            <input type="text" id="houseNumber" name="houseNumber">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="postalCode">PLZ:*</label>
                            <input type="text" id="postalCode" name="postalCode" pattern="[0-9]{5}">
                        </div>
                        <div class="form-group">
                            <label for="city">Stadt:*</label>
                            <input type="text" id="city" name="city">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Anmerkungen:</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>

                <button type="submit" name="submit_order" class="submit-button">Bestellen</button>
            </form>
            </div>
        HTML;
        echo '</div>';

        // Right column - Order summary
        echo '<div class="order-summary-container">';
        echo '<div class="order-summary">';
        echo '<h2>Ihre Bestellung</h2>';

        
        if (empty($orderItems)) {
            echo '<p class="empty-cart">Ihr Warenkorb ist leer</p>';
        } else {
            echo '<div class="order-items">';
            echo '<ul class="items-list">';
            
            $total = 0;
            foreach ($orderItems as $item) {
                $optionsHtml = '';
                if (!empty($item['options'])) {
                    $optionsHtml = '<div class="item-options">';
                    foreach ($item['options'] as $option) {
                        $priceText = $option['price'] > 0 ? ' (+' . number_format($option['price'], 2) . '€)' : 
                                    ($option['price'] < 0 ? ' (' . number_format($option['price'], 2) . '€)' : '');
                        $optionsHtml .= '<span class="option-tag">' . htmlspecialchars($option['name']) . $priceText . '</span>';
                    }
                    $optionsHtml .= '</div>';
                }
                
                echo <<<EOT
                <li class="order-item">
                    <div class="item-details">
                        <div class="item-main">
                            <span class="item-quantity">{$item['quantity']}x</span>
                            <span class="item-name">{$item['name']}</span>
                        </div>
                        {$optionsHtml}
                        <div class="item-meta">
                            <span class="item-total">{$item['total']} €</span>
                        </div>
                    </div>
                </li>
                EOT;
                $total += $item['total'];
            }
            
            echo '</ul>';
            echo '<div class="order-total">Gesamtsumme: ' . number_format($total, 2) . ' €</div>';
            echo '</div>';
        }
        
        echo <<< 'HTML'
        </div>
        </div>
        
        <script>
        function toggleDeliveryFields() {
            const orderType = document.getElementById('orderType').value;
            const deliveryFields = document.getElementById('deliveryFields');
            const requiredFields = ['street', 'houseNumber', 'postalCode', 'city'];
            
            if (orderType === 'delivery') {
                deliveryFields.style.display = 'block';
                requiredFields.forEach(field => {
                    document.getElementById(field).required = true;
                });
            } else {
                deliveryFields.style.display = 'none';
                requiredFields.forEach(field => {
                    document.getElementById(field).required = false;
                });
            }
        }
        </script>
        HTML;
        echo '</div>'; // menu-section
        echo '</div></div>'; // main-container
        echo <<< 'HTML'
            <style>
                .alert {
                    padding: 1rem;
                    margin: 1rem 0;
                    border-radius: 4px;
                    font-weight: bold;
                }
                
                .alert-error {
                    background-color: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                
                .alert-success {
                    background-color: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                
                .main-container {
                    margin: 0 auto;
                    padding: 5% 1rem 1rem 1rem;
                }
                
                .order-grid {
                    width: 90%;
                    display: grid;
                    grid-template-columns: 2fr 1fr;  /* Changed to 2:1 ratio */
                    gap: 2rem;
                    position: relative;
                }
                
                .order-form-container {
                    min-width: 0;  /* Allows the container to shrink below its content size */
                    width: 100%;
                }
                
                .order-form {
                    background: white;
                    padding: 2rem;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    height: fit-content;
                }
                
                /* Form styles */
                .form-group {
                    margin-bottom: 1.5rem;
                    width: 100%;
                }
                
                .form-row {
                    display: flex;
                    gap: 1rem;
                    margin-bottom: 1rem;
                    width: 100%;
                }
                
                .order-summary-container {
                    position: sticky;
                    top: 2rem;
                    height: fit-content;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    min-width: 300px;  /* Minimum width for readability */
                }
                
                .order-summary {
                    padding: 1.5rem;
                    max-height: calc(100vh - 4rem);
                    overflow-y: auto;
                }

                .order-summary h2 {
                    color: #333;
                    margin-bottom: 2rem;
                    padding-bottom: 1rem;
                    border-bottom: 2px solid #eee;
                }

                .items-list {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                }

                .order-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    padding: 1rem 0;
                    border-bottom: 1px solid #eee;
                }

                .item-details {
                    display: flex;
                    flex-direction: column;
                    width: 100%;
                    gap: 0.5rem;
                }

                .item-main {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }

                .item-name {
                    font-weight: bold;
                    flex: 1;
                }

                .item-options {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 0.25rem;
                    margin-left: 3rem;
                }

                .option-tag {
                    display: inline-block;
                    background: #e3f2fd;
                    color: #1976d2;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 11px;
                    border: 1px solid #bbdefb;
                    white-space: nowrap;
                }

                .item-meta {
                    display: flex;
                    justify-content: flex-end;
                    align-items: center;
                    margin-top: 0.5rem;
                }

                .item-quantity {
                    color: #666;
                    min-width: 3rem;
                    text-align: center;
                    font-weight: bold;
                    
                }

                .item-price {
                    color: #666;
                    min-width: 5rem;
                    text-align: right;
                }

                .item-total {
                    font-weight: bold;
                    min-width: 6rem;
                    text-align: right;
                }

                .order-total {
                    margin-top: 2rem;
                    padding-top: 1rem;
                    border-top: 2px solid #eee;
                    text-align: right;
                    font-size: 1.2em;
                    font-weight: bold;
                }

                .empty-cart {
                    text-align: center;
                    color: #666;
                    font-style: italic;
                    padding: 2rem;
                }

               
                .form-group label {
                    display: block;
                    margin-bottom: 0.5rem;
                    font-weight: bold;
                }

                .form-group input,
                .form-group select,
                .form-group textarea {
                    width: 100%;
                    padding: 0.5rem;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 1rem;
                }

                .form-row .form-group {
                    flex: 1;
                    margin-bottom: 0;
                }

                .submit-button {
                    background: #ffab66;
                    color: white;
                    border: none;
                    padding: 1rem 2rem;
                    border-radius: 4px;
                    font-size: 1.1rem;
                    font-weight: bold;
                    cursor: pointer;
                    width: 100%;
                    margin-top: 1rem;
                }

                .submit-button:hover {
                    background: #ff9240;
                }

                textarea {
                    resize: vertical;
                    min-height: 80px;
                }
                /* Responsive Design */
                @media (max-width: 1024px) {
                    .main-container {
                        padding: 1rem;
                    }
                
                    .order-grid {
                        grid-template-columns: 1fr;  /* Stack on mobile */
                        gap: 1rem;
                    }
                    
                    .order-summary-container {
                        position: relative;
                        top: 0;
                        max-height: 100vh; /* Limit height on mobile */
                        display: flex;
                        flex-direction: column;
                    }
                    
                    .order-summary {
                        display: flex;
                        flex-direction: column;
                        height: 100%;
                        padding: 1.5rem;
                        max-height: none;
                        overflow: hidden;
                    }
                    
                    .order-summary h2 {
                        flex-shrink: 0; /* Keep header visible */
                        margin-bottom: 1rem;
                        padding-bottom: 0.5rem;
                    }
                    
                    .order-items {
                        flex: 1;
                        overflow-y: auto;
                        margin-bottom: 1rem;
                        padding-right: 0.5rem;
                    }
                    
                    .order-total {
                        flex-shrink: 0; /* Keep total visible */
                        margin-top: 0;
                        padding-top: 1rem;
                        border-top: 2px solid #eee;
                        background: white;
                        position: sticky;
                        bottom: 0;
                    }

                    .order-form-container {
                        padding-right: 0;
                    }
                    /* Update existing form styles */
                    .order-form {
                        background: white;
                        padding: 2rem;
                        border-radius: 8px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    .order-form-container {
                        padding: 0;
                        width: 100%;
                    }
                
                    .order-form {
                        padding: 1.5rem;
                        margin: 0;
                        width: 100%;
                        box-sizing: border-box;
                    }
                
                    .order-summary-container {
                        position: relative;
                        top: 0;
                        width: 100%;
                        margin-top: 1rem;
                    }
                
                    .form-row {
                        flex-direction: column;
                        gap: 0.5rem;
                    }
                
                    .form-row .form-group {
                        width: 100%;
                    }
                    .form-group {
                        margin-bottom: 1.5rem;
                    }
                    .form-group label {
                        display: block;
                        margin-bottom: 0.5rem;
                        font-weight: 600;
                        color: #333;
                    }
                    .form-group input,
                    .form-group select,
                    .form-group textarea {
                        width: 95%;
                        padding: 0.75rem;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        font-size: 1rem;
                        transition: border-color 0.2s;
                    }
                    .form-group input:focus,
                    .form-group select:focus,
                    .form-group textarea:focus {
                        border-color: #ffab66;
                        outline: none;
                        box-shadow: 0 0 0 2px rgba(255, 171, 102, 0.2);
                    }

                    /* Mobile adjustments for order items */
                    .item-main {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 0.25rem;
                    }

                    .item-options {
                        margin-left: 0;
                        margin-top: 0.25rem;
                    }

                    .option-tag {
                        font-size: 10px;
                        padding: 1px 6px;
                    }

                    .item-meta {
                        margin-top: 0.25rem;
                    }
                }
            </style>
            HTML;
    }


}

OrderSubmit::main();