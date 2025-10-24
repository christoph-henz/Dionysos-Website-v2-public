<?php

namespace Dionysosv2\Views;
use Dionysosv2\Controller\CartController;
use Dionysosv2\Controller\SettingsController;
use Dionysosv2\Models\Invoice;
use Dionysosv2\Services\TelegramBotService;
use Dionysosv2\Services\EmailService;
use Exception;

class Reservation extends Page
{
    /**
     * Properties
     */
    private SettingsController $settingsController;

    /**
     * Instantiates members (to be defined above).
     * Calls the constructor of the parent i.e. page class.
     * So, the database connection is established.
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct();
        try {
            $this->settingsController = new SettingsController();
        } catch (Exception $e) {
            // Fallback: Settings-System deaktiviert
            error_log("Settings Controller Fehler: " . $e->getMessage());
        }
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
        // Generiere ein zufälliges Token und speichere es in der Sitzung
        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
        try {
            $page = new Reservation();
            $page->processReceivedData();
            $page->generateView();
        } catch (Exception $e) {
            //header("Content-type: text/plain; charset=UTF-8");
            header("Content-type: text/html; charset=UTF-8");
            echo $e->getMessage();
        }

        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
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
        
        // AJAX-Request für Zeitslots eines bestimmten Datums
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_time_slots') {
            // Ensure session is started for this request too
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            header('Content-Type: application/json');
            
            $date = $_POST['date'] ?? null;
            if ($date && $this->settingsController) {
                try {
                    $timeSlots = $this->settingsController->getTimeSlotsForDate($date);
                    
                    // Für heute: Filtere Zeitslots die weniger als 2 Stunden entfernt sind
                    if ($date === date('Y-m-d')) {
                        $now = new \DateTime();
                        $minTime = $now->add(new \DateInterval('PT2H'))->format('H:i');
                        
                        $timeSlots = array_filter($timeSlots, function($slot) use ($minTime) {
                            return $slot >= $minTime;
                        });
                        $timeSlots = array_values($timeSlots); // Re-index array
                    }
                    
                    // Fallback falls keine Zeitslots zurückgegeben werden
                    if (empty($timeSlots)) {
                        $timeSlots = ['17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30'];
                        
                        // Für heute: Filtere Zeitslots die weniger als 2 Stunden entfernt sind
                        if ($date === date('Y-m-d')) {
                            $now = new \DateTime();
                            $minTime = $now->add(new \DateInterval('PT2H'))->format('H:i');
                            
                            $timeSlots = array_filter($timeSlots, function($slot) use ($minTime) {
                                return $slot >= $minTime;
                            });
                            $timeSlots = array_values($timeSlots); // Re-index array
                        }
                    }
                    
                    echo json_encode(['success' => true, 'time_slots' => $timeSlots]);
                } catch (Exception $e) {
                    error_log("Zeitslot-Fehler: " . $e->getMessage());
                    // Fallback Zeitslots bei Fehler
                    $timeSlots = ['17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30'];
                    
                    // Für heute: Filtere Zeitslots die weniger als 2 Stunden entfernt sind
                    if ($date === date('Y-m-d')) {
                        $now = new \DateTime();
                        $minTime = $now->add(new \DateInterval('PT2H'))->format('H:i');
                        
                        $timeSlots = array_filter($timeSlots, function($slot) use ($minTime) {
                            return $slot >= $minTime;
                        });
                        $timeSlots = array_values($timeSlots); // Re-index array
                    }
                    
                    echo json_encode(['success' => true, 'time_slots' => $timeSlots]);
                }
            } else {
                // Fallback Zeitslots
                $timeSlots = ['17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30'];
                
                // Für heute: Filtere Zeitslots die weniger als 2 Stunden entfernt sind
                if ($date === date('Y-m-d')) {
                    $now = new \DateTime();
                    $minTime = $now->add(new \DateInterval('PT2H'))->format('H:i');
                    
                    $timeSlots = array_filter($timeSlots, function($slot) use ($minTime) {
                        return $slot >= $minTime;
                    });
                    $timeSlots = array_values($timeSlots); // Re-index array
                }
                
                echo json_encode(['success' => true, 'time_slots' => $timeSlots]);
            }
            exit;
        }
        
        // Prüfe ob Reservierungssystem aktiviert ist (nur bei POST-Anfragen mit action)
        if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']))) {
            if ($this->settingsController && !$this->settingsController->isFeatureEnabled('reservation_system')) {
                header('Content-Type: application/json');
                header('HTTP/1.0 503 Service Unavailable');
                echo json_encode(['success' => false, 'message' => 'Das Reservierungssystem ist derzeit nicht verfügbar.']);
                exit;
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_reservation') {
            // CSRF-Token überprüfen
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['token'] ?? '')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Ungültiger Sicherheitstoken']);
                exit;
            }
            
            // Formular-Daten validieren
            $required_fields = ['firstName', 'lastName', 'email', 'phone', 'date', 'time', 'guests'];
            $errors = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    $errors[] = "Das Feld '$field' ist erforderlich.";
                }
            }
            
            // E-Mail validieren
            if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ungültige E-Mail-Adresse.";
            }
            
            // Datum und Zeit validieren
            if (!empty($_POST['date'])) {
                $reservationDate = new \DateTime($_POST['date']);
                $today = new \DateTime();
                $today->setTime(0, 0, 0); // Setze auf Mitternacht für Datumsvergleich
                $maxAdvanceDays = $this->settingsController ? $this->settingsController->getSetting('reservation_advance_days', 30) : 30;
                $maxDate = new \DateTime("+{$maxAdvanceDays} days");
                
                if ($reservationDate < $today) {
                    $errors[] = "Das Reservierungsdatum darf nicht in der Vergangenheit liegen.";
                } elseif ($reservationDate->format('Y-m-d') === (new \DateTime())->format('Y-m-d')) {
                    // Heute: Prüfe ob mindestens 2 Stunden im Voraus
                    if (!empty($_POST['time'])) {
                        $reservationDateTime = new \DateTime($_POST['date'] . ' ' . $_POST['time']);
                        $minReservationTime = new \DateTime('+2 hours');
                        
                        if ($reservationDateTime < $minReservationTime) {
                            $errors[] = "Reservierungen am heutigen Tag müssen mindestens 2 Stunden im Voraus erfolgen.";
                        }
                    }
                } elseif ($reservationDate > $maxDate) {
                    $errors[] = "Reservierungen sind nur bis zu {$maxAdvanceDays} Tage im Voraus möglich.";
                }
            }
            
            // Personenanzahl validieren
            if (!empty($_POST['guests'])) {
                $guests = $_POST['guests'] === 'more' ? (int)($_POST['customGuests'] ?? 0) : (int)$_POST['guests'];
                $maxPartySize = $this->settingsController ? $this->settingsController->getSetting('reservation_max_party_size', 20) : 20;
                
                if ($guests > $maxPartySize) {
                    $errors[] = "Die maximale Personenanzahl pro Reservierung beträgt {$maxPartySize}.";
                }
            }
            
            // Anmerkungsfeld auf Außenbereich-Begriffe prüfen
            if (!empty($_POST['notes'])) {
                $notes = strtolower($_POST['notes']);
                $outdoorKeywords = [
                    'außenbereich', 'aussenbereich', 'außen', 'aussen',
                    'draußen', 'draussen', 'drausen', 'main', 'terasse', 
                    'terrasse', 'garten', 'outdoor', 'balkon', 'veranda',
                    'freiluft', 'im freien', 'auf der terrasse', 'am main',
                    'außenterrasse', 'ausenterrasse'
                ];
                
                foreach ($outdoorKeywords as $keyword) {
                    if (strpos($notes, $keyword) !== false) {
                        $errors[] = "Reservierungen sind nur für den Innenbereich möglich. Für Fragen zu Plätzen im Außenbereich kontaktieren Sie uns bitte telefonisch unter 06021 25779.";
                        break; // Einen Fehler reicht aus
                    }
                }
            }
            
            if (empty($errors)) {
                try {
                    // Reservierung in Datenbank speichern
                    $this->saveReservation($_POST);
                    
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Reservierung erfolgreich gespeichert']);
                    exit;
                } catch (Exception $e) {
                    error_log("Reservierung Fehler: " . $e->getMessage());
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern der Reservierung: ' . $e->getMessage()]);
                    exit;
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
                exit;
            }
        }
    }
    
    private function saveReservation($data): void
    {
        // Gästeanzahl bestimmen
        $guests = $data['guests'] === 'more' ? (int)$data['customGuests'] : (int)$data['guests'];
        
        // Unterschiedliche SQL-Syntax je nach Datenbanktyp
        if ($this->isLocal) {
            // SQLite Syntax
            $stmt = $this->_database->prepare("
                INSERT INTO reservations (
                    first_name, last_name, email, phone, 
                    reservation_date, reservation_time, guests, 
                    notes, created_at, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), 'pending')
            ");
            
            $stmt->execute([
                $data['firstName'],
                $data['lastName'], 
                $data['email'],
                $data['phone'],
                $data['date'],
                $data['time'],
                $guests,
                $data['notes'] ?? null
            ]);
        } else {
            // MySQL Syntax
            $stmt = $this->_database->prepare("
                INSERT INTO reservations (
                    first_name, last_name, email, phone, 
                    reservation_date, reservation_time, guests, 
                    notes, created_at, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
            ");
            
            $stmt->execute([
                $data['firstName'],
                $data['lastName'], 
                $data['email'],
                $data['phone'],
                $data['date'],
                $data['time'],
                $guests,
                $data['notes'] ?? null
            ]);
        }
        
        // Reservierungs-ID abrufen
        $reservationId = $this->_database->lastInsertId();
        
        // Telegram-Benachrichtigung senden
        $this->sendTelegramReservationNotification($reservationId);
    }

    /**
     * Sendet Telegram-Benachrichtigung über neue Reservierung
     */
    private function sendTelegramReservationNotification(int $reservationId): void
    {
        try {
            $telegramService = new TelegramBotService($this->_database);
            $telegramService->sendReservationNotification($reservationId);
        } catch (Exception $e) {
            // Telegram-Fehler sollten die Reservierung nicht verhindern
            error_log("Telegram-Reservierungsbenachrichtigung fehlgeschlagen: " . $e->getMessage());
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
        // Prüfe ob Reservierungssystem aktiviert ist (mit Fallback)
        if ($this->settingsController && !$this->settingsController->isFeatureEnabled('reservation_system')) {
            $this->generatePageHeader('Reservierungssystem nicht verfügbar');
            $this->generateReservationSystemDisabledView();
            $this->generatePageFooter();
            return;
        }

        $this->generatePageHeader('Reservierung'); //to do: set optional parameters

        //$this->generateNav();
        $this->generateMainBody();
        $this->generatePageFooter();
    }

    /**
     * Generiert die Standby-UI wenn das Reservierungssystem deaktiviert ist
     */
    private function generateReservationSystemDisabledView(): void
    {
        $this->generateIntroDisplay();
        
        echo <<<HTML
            <div class="main-container">
                <div class="system-disabled-container">
                    <div class="system-disabled-content">
                        <div class="disabled-icon">📅</div>
                        <h1>Reservierungssystem momentan nicht verfügbar</h1>
                        <p>Unser Online-Reservierungssystem ist derzeit deaktiviert. Wir bitten um Ihr Verständnis.</p>
                        <div class="alternative-options">
                            <h3>Alternative Reservierungsmöglichkeiten:</h3>
                            <div class="contact-option">
                                <div class="contact-icon">📞</div>
                                <div class="contact-info">
                                    <strong>Telefonisch reservieren</strong>
                                    <p>06021 25779</p>
                                    <p>Montag - Sonntag: 17:30 - 22:00 Uhr</p>
                                </div>
                            </div>
                            <div class="contact-option">
                                <div class="contact-icon">🏃</div>
                                <div class="contact-info">
                                    <strong>Direkt im Restaurant</strong>
                                    <p>Floßhafen 27, 63739 Aschaffenburg</p>
                                    <p>Spontane Plätze nach Verfügbarkeit</p>
                                </div>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <a href="/" class="btn btn-primary">Zurück zur Startseite</a>
                            <a href="/order" class="btn btn-secondary">Online bestellen</a>
                        </div>
                    </div>
                </div>
            </div>
        HTML;
        
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
                    opacity: 0.6;
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
                    align-items: flex-start;
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
                    margin: 0.25rem 0;
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
        echo <<< EOT
            <link rel="stylesheet" type="text/css" href="public/assets/css/home.css"/>
            <link rel="stylesheet" type="text/css" href="public/assets/css/reservation.css"/>
            <script src="public/assets/js/home-behavior.js"></script> 
            <script src="public/assets/js/reservation-behaviour.js"></script>
        EOT;
    }

    private function generateIntroDisplay() : void{
        echo <<<HTML
            <!-- Sticky Header -->
            <header class="sticky-header">
                <nav class="menu">  
                    <a href="/">Startseite</a>
                    <a href="/reservation">Reservierung</a>
                    <a href="/#openings">Öffnungszeiten</a>
                </nav>
                <div class="logo">
                    <img src="public/assets/img/logo.png" alt="LOGO"/>
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
                  <a href="tel:0602125779"><img src="public/assets/img/phone.svg" style="filter: invert(1);" alt="Phone" />06021 25779</a>
                  <a href="mailto:info@dionysos-aburg.de"><img src="public/assets/img/mail.svg" alt="Mail" />info@dionysos-aburg.de</a>
                  <a href="https://www.instagram.com/dionysos_aburg/?hl=de" target="_blank"><img src="public/assets/img/instagram.svg" alt="Instagram" />Instagram</a>
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
                <div class="logo"><img src="public/assets/img/logo.png" alt="LOGO"/></div>
                <nav class="menu">
                  <a href="/order">Bestellen</a>
                  <a href="/#gallery">Gallerie</a>
                  <a href="/#contact">Kontakt</a>
                </nav>
                </div>
            </div></div>
        HTML;
    }

    private function generateBody()
    {
        // Prüfe ob Reservierungssystem aktiviert ist (mit Fallback)
        if ($this->settingsController && !$this->settingsController->isFeatureEnabled('reservation_system')) {
            echo <<<HTML
                <div class="main-container">
                    <div class="reservation-container">
                        <div class="reservation-header">
                            <h1>Reservierungen derzeit nicht verfügbar</h1>
                            <p>Das Reservierungssystem ist derzeit deaktiviert. Bitte kontaktieren Sie uns telefonisch.</p>
                        </div>
                    </div>
                </div>
            HTML;
            return;
        }

        // Hole Einstellungen mit Fallback-Werten
        if ($this->settingsController) {
            $reservationSettings = $this->settingsController->getReservationSettings();
            $restaurantInfo = $this->settingsController->getRestaurantInfo();
            $maxAdvanceDays = $reservationSettings['advance_days'];
            $maxPartySize = $reservationSettings['max_party_size'];
        } else {
            // Fallback-Werte wenn Settings nicht verfügbar
            $maxAdvanceDays = 30;
            $maxPartySize = 20;
            $restaurantInfo = [
                'phone' => '06021 25779',
                'email' => 'info@dionysos-aburg.de'
            ];
        }
        
        // Berechne maximales Datum
        $maxDate = date('Y-m-d', strtotime("+{$maxAdvanceDays} days"));
        
        echo <<<HTML
            <div class="main-container">
                <div class="reservation-container">
                    <div class="reservation-header">
                        <h1>Tischreservierung</h1>
                        <p>Reservieren Sie bequem online einen Tisch in unserem Restaurant. Wir freuen uns auf Ihren Besuch!</p>
                        <div class="reservation-notice">
                            <p><strong>Wichtiger Hinweis:</strong> Alle Reservierungen gelten ausschließlich für unseren Innenbereich. Tische im Außenbereich können nicht reserviert werden.</p>
                        </div>
                    </div>
                    
                    <div class="reservation-form-container">
                        <form id="reservationForm" action="/reservation" method="POST">
                            <input type="hidden" name="action" value="submit_reservation">
                            <input type="hidden" name="csrf_token" value="{$_SESSION['token']}">
                            
                            <div class="form-section">
                                <h3>Persönliche Daten</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="firstName">Vorname *</label>
                                        <input type="text" id="firstName" name="firstName" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="lastName">Nachname *</label>
                                        <input type="text" id="lastName" name="lastName" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">E-Mail-Adresse *</label>
                                        <input type="email" id="email" name="email" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Telefonnummer *</label>
                                        <input type="tel" id="phone" name="phone" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Reservierungsdetails</h3>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="date">Datum *</label>
                                        <input type="date" id="date" name="date" required min="' . date('Y-m-d') . '" max="{$maxDate}">
                                    </div>
                                    <div class="form-group">
                                        <label for="time">Uhrzeit *</label>
                                        <select id="time" name="time" required disabled>
                                            <option value="">Bitte wählen Sie zuerst ein Datum...</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="guests">Anzahl Personen *</label>
                                        <select id="guests" name="guests" required>
                                            <option value="">Bitte wählen...</option>
        HTML;
        
        // Gästeoptionen dynamisch generieren
        for ($i = 1; $i <= min(10, $maxPartySize); $i++) {
            $persons = $i === 1 ? 'Person' : 'Personen';
            echo "<option value=\"{$i}\">{$i} {$persons}</option>";
        }
        
        if ($maxPartySize > 10) {
            echo '<option value="more">Mehr als 10 Personen</option>';
        }
        
        echo <<<HTML
                                        </select>
                                    </div>
                                    <div class="form-group" id="customGuestsGroup" style="display: none;">
                                        <label for="customGuests">Genaue Anzahl </label>
                                        <input type="number" id="customGuests" name="customGuests" min="11" max="{$maxPartySize}">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Zusätzliche Informationen</h3>
                                <div class="form-group">
                                    <label for="notes">Anmerkungen (optional)</label>
                                    <textarea id="notes" name="notes" rows="4" placeholder="Besondere Wünsche, Allergien, Anlass der Feier, etc."></textarea>
                                    <div id="outdoorWarning" class="outdoor-warning" style="display: none;">
                                        <p><strong>⚠️ Hinweis:</strong> Reservierungen sind nur für den Innenbereich möglich.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Reservierung abschicken</button>
                                <button type="reset" class="btn-secondary">Formular zurücksetzen</button>
                            </div>
                        </form>
                    </div>
        HTML;
        
        // Restaurant-Info aus Einstellungen oder Fallback
        echo '<div class="reservation-info">';
        echo '<div class="info-box">';
        echo '<h4>Öffnungszeiten</h4>';
        
        if ($this->settingsController) {
            echo $this->settingsController->generateOpeningHoursDisplay();
        } else {
            echo <<<HTML
                <div class="opening-hours">
                    <div class="day-schedule"><strong>Montag:</strong> <span class="closed">Ruhetag</span></div>
                    <div class="day-schedule"><strong>Dienstag - Samstag:</strong> 17:00 - 23:00 Uhr</div>
                    <div class="day-schedule"><strong>Sonntag:</strong> <span class="closed">Ruhetag</span></div>
                </div>
            HTML;
        }
        
        echo <<<HTML
            </div>
            
            <div class="info-box">
                <h4>Kontakt</h4>
                <p><strong>Telefon:</strong><br>{$restaurantInfo['phone']}</p>
                <p><strong>E-Mail:</strong><br>{$restaurantInfo['email']}</p>
            </div>
            
            <div class="info-box">
                <h4>Hinweise</h4>
                <ul>
                    <li><strong>Alle Reservierungen gelten ausschließlich für den Innenbereich</strong></li>
                    <li>Reservierungen für den Außenbereich sind nicht möglich</li>
                    <li>Reservierungen werden zeitnah bestätigt</li>
                    <li>Bei Großgruppen (>{$maxPartySize} Personen) rufen Sie bitte an</li>
                    <li>Stornierungen bis 24 Stunden vorher möglich</li>
                </ul>
            </div>
        </div>
    </div>
</div>
HTML;
        
        // JavaScript wird über separate Datei geladen
        echo '<script>';
        echo 'document.getElementById("date").min = new Date().toISOString().split("T")[0];';
        echo 'document.getElementById("date").max = "' . $maxDate . '";';
        echo '</script>';
    }


}

Reservation::main();