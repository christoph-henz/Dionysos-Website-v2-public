<?php

namespace Dionysosv2\Views;
use Dionysosv2\Controller\MenuBuilder;
use Exception;

class Home extends Page
{
    /**
     * Properties
     */
    private array $systemSettings;

    /**
     * Instantiates members (to be defined above).
     * Calls the constructor of the parent i.e. page class.
     * So, the database connection is established.
     * @throws Exception
     */
    protected function __construct()
    {
        parent::__construct();
        $this->loadSystemSettings();
    }

    /**
     * Lädt die Systemeinstellungen aus der Datenbank
     */
    private function loadSystemSettings(): void
    {
        if ($this->isLocal) {
            // SQLite - keine besonderen Anpassungen nötig
            $stmt = $this->_database->prepare("
                SELECT setting_key, setting_value 
                FROM settings 
                WHERE setting_key IN ('reservation_system', 'order_system', 'pickup_system', 'delivery_system', 'reservation_system_enabled', 'order_system_enabled', 'pickup_system_enabled', 'delivery_system_enabled')
            ");
        } else {
            // MySQL - gleiche Abfrage
            $stmt = $this->_database->prepare("
                SELECT setting_key, setting_value 
                FROM settings 
                WHERE setting_key IN ('reservation_system', 'order_system', 'pickup_system', 'delivery_system', 'reservation_system_enabled', 'order_system_enabled', 'pickup_system_enabled', 'delivery_system_enabled')
            ");
        }
        
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        // Prüfe ob Restaurant geöffnet ist und Bestellungen angenommen werden
        $settingsModel = new \Dionysosv2\Models\Settings($this->_database);
        $isRestaurantOpen = $settingsModel->isOpen();
        $isOrderingAvailable = $settingsModel->isOrderingAvailable();
        
        // Explizite Typ-Konvertierung zu Boolean
        $reservationEnabled = $this->convertToBoolean($settings['reservation_system'] ?? $settings['reservation_system_enabled'] ?? '1');
        $orderEnabled = $this->convertToBoolean($settings['order_system'] ?? $settings['order_system_enabled'] ?? '1');
        $pickupEnabled = $this->convertToBoolean($settings['pickup_system'] ?? $settings['pickup_system_enabled'] ?? '1');
        $deliveryEnabled = $this->convertToBoolean($settings['delivery_system'] ?? $settings['delivery_system_enabled'] ?? '1');
        
        $this->systemSettings = [
            'reservation_system' => $reservationEnabled,
            'order_system' => $orderEnabled && $isOrderingAvailable,
            'pickup_system' => $pickupEnabled,
            'delivery_system' => $deliveryEnabled,
            'restaurant_open' => $isRestaurantOpen,
            'ordering_available' => $isOrderingAvailable
        ];
    }
    
    /**
     * Konvertiert verschiedene Werte zu Boolean
     */
    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'on', 'enabled'], true);
        }
        
        if (is_numeric($value)) {
            return (int)$value === 1;
        }
        
        return false;
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
            $page = new Home();
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
        // to do: call processReceivedData() for all members


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
        $this->generatePageHeader('Dionysos'); //to do: set optional parameters

        //$this->generateNav();
        $this->generateMainBody();
        $this->generatePageFooter();

                // Lazy-Loading für Galerie- und Menü-Skripte
                echo <<<HTML
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Galerie-Skript lazy laden
                        var gallery = document.getElementById('gallery');
                        if (gallery) {
                            var galleryObserver = new IntersectionObserver(function(entries) {
                                if (entries[0].isIntersecting) {
                                    var script = document.createElement('script');
                                    script.src = 'public/assets/js/gallery-behaviour.js';
                                    document.body.appendChild(script);
                                    galleryObserver.disconnect();
                                }
                            });
                            galleryObserver.observe(gallery);
                        }
                        // Menü-Skript lazy laden
                        var menu = document.getElementById('main-menu');
                        if (menu) {
                            var menuObserver = new IntersectionObserver(function(entries) {
                                if (entries[0].isIntersecting) {
                                    var script = document.createElement('script');
                                    script.src = 'public/assets/js/menu-behaviour.js';
                                    document.body.appendChild(script);
                                    menuObserver.disconnect();
                                }
                            });
                            menuObserver.observe(menu);
                        }
                    });
                    </script>
                HTML;
    }

    private function generateMainBody(){
        $this->generateIntroDisplay();
        $this->generateWelcomeDisplay();
        $this->generateOpeningDisplay();
        $this->generateMenuDisplay();
        $this->generateOrderDisplay();
        $this->generateGalleryDisplay();
        $this->generateApproachDisplay();
    }

    protected function additionalMetaData(): void
    {
        // SEO Meta-Tags und Links für css/js
    echo '<meta name="description" content="Erleben Sie griechische Gastfreundschaft am Main! Reservieren Sie jetzt Ihren Tisch im Dionysos Aschaffenburg, genießen Sie authentische Spezialitäten und ein mediterranes Ambiente direkt am Wasser.">';
    echo '<meta name="keywords" content="Restaurant, Dionysos, Aschaffenburg, griechisch, Reservierung, Bestellung, Speisekarte, Essen, Taverna, Terrasse, Main, Familienfeier, Event, Hochzeit, Firmenfeier, mediterran, Floßhafen, Griechenland">';
    echo '<meta property="og:title" content="Restaurant Dionysos Aschaffenburg">';
    echo '<meta property="og:description" content="Reservieren, bestellen und genießen Sie griechische Spezialitäten im Dionysos Aschaffenburg am Main.">';
    echo '<meta property="og:type" content="restaurant">';
    echo '<meta property="og:site_name" content="Restaurant Dionysos">';
    echo '<link rel="canonical" href="https://www.dionysos-aburg.de/">';
    echo '<link rel="preload" href="public/assets/css/home.css" as="style">';
    echo '<link rel="preload" href="public/assets/img/logo.png" as="image">';
    echo '<link rel="preload" href="public/assets/img/favicon.ico" as="image">';
    echo '<link rel="icon" type="image/x-icon" href="public/assets/img/favicon.ico">';
    echo '<link rel="stylesheet" type="text/css" href="public/assets/css/home.css"/>';
    echo '<script src="public/assets/js/home-behavior.js" defer></script>';
    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" defer></script>';
        // Strukturierte Daten für LocalBusiness/Restaurant
        echo '<script type="application/ld+json">'.json_encode([
            "@context" => "https://schema.org",
            "@type" => "Restaurant",
            "name" => "Restaurant Dionysos Aschaffenburg",
            "image" => "https://www.dionysos-aburg.de/public/assets/img/logo.png",
            "@id" => "https://www.dionysos-aburg.de/",
            "url" => "https://www.dionysos-aburg.de/",
            "telephone" => "06021 25779",
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => "Floßhafen 27",
                "addressLocality" => "Aschaffenburg",
                "postalCode" => "63739",
                "addressCountry" => "DE"
            ],
            "servesCuisine" => "Griechisch",
            "openingHours" => [
                "Tu-Sa 17:30-23:00",
                "Su 11:30-22:00"
            ],
            "priceRange" => "€€",
            "sameAs" => [
                "https://www.instagram.com/dionysos_aburg/?hl=de"
            ]
        ]).'</script>';
    }

    private function generateIntroDisplay() : void{
        // Dynamische Links basierend auf Systemeinstellungen
        $reservationLink = $this->systemSettings['reservation_system'] ? '/reservation' : '#';
        $orderLink = $this->systemSettings['order_system'] ? '/order' : '#';
        
        $reservationClass = $this->systemSettings['reservation_system'] ? '' : ' class="disabled-link"';
        $orderClass = $this->systemSettings['order_system'] ? '' : ' class="disabled-link"';
        
        echo '<!-- Sticky Header -->';
        echo '<header class="sticky-header">';
        echo '<nav class="menu">';
        echo '<a href="#">Startseite</a>';
        echo '<a href="' . $reservationLink . '"' . $reservationClass . '>Reservierung</a>';
        echo '<a href="#openings">Öffnungszeiten</a>';
        echo '</nav>';
        echo '<div class="logo">';
        echo '<img src="public/assets/img/logo.png" alt="LOGO"/>';
        echo '</div>';
        echo '<nav class="menu">';
        echo '<a href="' . $orderLink . '"' . $orderClass . '>Bestellen</a>';
        echo '<a href="#gallery">Gallerie</a>';
        echo '<a href="#contact">Kontakt</a>';
        echo '</nav>';
        echo '</header>';
        echo '<div class="intro-container">';
        echo '<!-- Oberer Header -->';
        echo '<div class="top-header">';
        echo '<div class="social-icons">';
        echo '<a href="tel:0602125779"><img src="public/assets/img/phone.svg" style="filter: invert(1);" alt="Phone" />06021 25779</a>';
        echo '<a href="mailto:info@dionysos-aburg.de"><img src="public/assets/img/mail.svg" alt="Mail" />info@dionysos-aburg.de</a>';
        echo '<a href="https://www.instagram.com/dionysos_aburg/?hl=de" target="_blank"><img src="public/assets/img/instagram.svg" alt="Instagram" />Instagram</a>';
        echo '</div>';
        echo '</div>';
        echo '<!-- Unterer Header -->';
        echo '<div class="main-header">';
        echo '<div class="menu-toggle">&#9776;</div>'; 
        echo '<!-- Menü für mobile Ansicht -->';
        echo '<nav class="menu" id="mobile-menu">';
        echo '<a href="#">Startseite</a>';
        echo '<a href="' . $reservationLink . '"' . $reservationClass . '>Reservierung</a>';
        echo '<a href="#openings">Öffnungszeiten</a>';
        echo '<a href="' . $orderLink . '"' . $orderClass . '>Bestellen</a>';
        echo '<a href="#gallery">Gallerie</a>';
        echo '<a href="#contact">Kontakt</a>';
        echo '</nav>';
        echo '<nav class="menu">';
        echo '<a href="#">Startseite</a>';
        echo '<a href="' . $reservationLink . '"' . $reservationClass . '>Reservierung</a>';
        echo '<a href="#openings">Öffnungszeiten</a>';
        echo '</nav>';
        echo '<div class="logo"><img src="public/assets/img/logo.png" alt="LOGO"/></div>';
        echo '<nav class="menu">';
        echo '<a href="' . $orderLink . '"' . $orderClass . '>Bestellen</a>';
        echo '<a href="#gallery">Gallerie</a>';
        echo '<a href="#contact">Kontakt</a>';
        echo '</nav>';
        echo '</div>';
        echo '<!-- Titelbereich -->';
        echo '<div class="intro-content">';
        echo '<h1 class="title">Dionysos</h1>';
        echo '<h2 class="subtitle">Der Grieche am Main</h2>';
        
        // Dynamischer Reservierungsbutton
        if ($this->systemSettings['reservation_system']) {
            echo '<a class="intro-reservation" href="/reservation">Jetzt Reservieren</a>';
        } else {
            echo '<div class="intro-reservation disabled" title="Reservierungssystem derzeit nicht verfügbar">Reservierung nicht verfügbar</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }

    private function generateWelcomeDisplay() : void{
        echo <<< EOT
            <div class="welcome-section">
              <div class="welcome-text">
                <h2>Willkommen bei uns!</h2>
                <p>
                  Wir freuen uns, Sie auf unserer Website begrüßen zu dürfen. Entdecken Sie unsere Angebote und erfahren Sie mehr über unsere Leidenschaft und unseren Service.
                </p>
              </div>
              <div class="welcome-image">
                <img src="public/assets/img/1.jpg" alt="Willkommensbild" />
              </div>
            </div>
        EOT;
    }

    private function generateOpeningDisplay() : void{
        echo <<< EOT
            <div class="opening-hours-section" id="openings">
              <div class="opening-hours-bg"></div> <!-- Hintergrundbild -->
                <div class="opening-hours-content">
                  <h2 class="opening-hours-title">Öffnungszeiten</h2>
                  <div class="opening-hours-wrapper">
                    <div class="opening-hours-box">
                      <h3 class="opening-days">Di - Sa</h3>
                      <p class="opening-time">17:30 - 23:00</p>
                      <p class="kitchen">Warme Küche</p>
                      <p class="kitchen-time">17:30 - 21:30</p>
                    </div>
                    <div class="opening-hours-box">
                      <h3 class="opening-days">So</h3>
                      <p class="opening-time">11:30 - 22:00</p>
                      <p class="kitchen">Warme Küche</p>
                      <p class="kitchen-time">11:30 - 21:00</p>
                    </div>
                  </div>
                  <div class="monday-rest-day">
                    <p>Montag Ruhetag</p>
                  </div>
                  <div class="phone-number">
                    <p>Tel: 06021 25779</p>
                  </div>
              </div>
            </div>
        EOT;
    }

    private function generateMenuDisplay(): void
    {
        try {
            $builder = new MenuBuilder();
            
            // PDF nur generieren wenn sie nicht existiert oder älter als 1 Stunde ist
            $pdfPath = __DIR__ . '/../../public/speisekarte.pdf';
            if (!file_exists($pdfPath) || (time() - filemtime($pdfPath)) > 3600) {
                try {
                    $builder->generatePdf();
                } catch (Exception $e) {
                    error_log("PDF-Generierung fehlgeschlagen: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("MenuBuilder Fehler: " . $e->getMessage());
        }

        echo <<<EOT
            <div id="main-menu" class="menu-section">
                <div class="menu-navigation">
                    <button class="menu-nav-button" id="firstPage" title="Erste Seite">⟪</button>
                    <button class="menu-nav-button" id="prevPage" title="Vorherige Seite">←</button>
                    <div>
                        <span>Seite <span id="pageNum">1</span> / <span id="pageCount">?</span></span>
                    </div>
                    <button class="menu-nav-button" id="nextPage" title="Nächste Seite">→</button>
                    <button class="menu-nav-button" id="lastPage" title="Letzte Seite">⟫</button>
                </div>
                <div style="display: flex; flex-direction: column; align-items: center; gap: 10px;">
                    
            
                    <!-- Canvas-Container -->
                    <div class="page-wrapper">
                        <div id="canvasContainer" class="page-flip">
                            <canvas id="pdfCanvas1" class="canvas-page"></canvas>
                            <canvas id="pdfCanvas2" class="canvas-page" style="display: none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
            <script src="public/assets/js/menu-behaviour.js"></script>
        EOT;
    }

    private function generateOrderDisplay(): void
    {
        if ($this->systemSettings['order_system']) {
            // Bestimme den dynamischen Titel basierend auf verfügbaren Services
            $pickupEnabled = $this->systemSettings['pickup_system'];
            $deliveryEnabled = $this->systemSettings['delivery_system'];
            
            if ($pickupEnabled && $deliveryEnabled) {
                $orderTitle = "Mitnahme & Lieferung";
                $orderText = "Liebe Gäste, selbstverständlich können sie all unsere Speisen auch zum Abholen oder zur Lieferung bestellen. Nutzen Sie hierfür die Online Bestellfunktion.";
            } elseif ($pickupEnabled && !$deliveryEnabled) {
                $orderTitle = "Mitnahme";
                $orderText = "Liebe Gäste, selbstverständlich können sie all unsere Speisen auch zum Abholen bestellen. Nutzen Sie hierfür die Online Bestellfunktion.";
            } elseif (!$pickupEnabled && $deliveryEnabled) {
                $orderTitle = "Lieferung";
                $orderText = "Liebe Gäste, wir liefern Ihnen gerne all unsere Speisen direkt nach Hause. Nutzen Sie hierfür die Online Bestellfunktion.";
            } else {
                // Wenn weder Pickup noch Delivery aktiviert ist, aber Order-System an
                $orderTitle = "Online Bestellung";
                $orderText = "Unser Online-Bestellsystem ist verfügbar. Kontaktieren Sie uns für weitere Informationen.";
            }
            
            echo <<< EOT
                <div class="order-section">
                <div class="order-info">
                  <div class="order-text">
                    <h2>{$orderTitle}</h2>
                    <p>
                      {$orderText}
                    </p>
                    <a class="order-button" href="/order">Jetzt Bestellen</a>
                  </div>
                  <div class="order-image">
                    <img src="public/assets/img/5.jpg" alt="Willkommensbild" />
                  </div>
                  </div>
                </div>
            EOT;
        } else {
            // Auch bei deaktiviertem System die korrekte Titel-Logik anwenden
            $pickupEnabled = $this->systemSettings['pickup_system'];
            $deliveryEnabled = $this->systemSettings['delivery_system'];
            
            if ($pickupEnabled && $deliveryEnabled) {
                $orderTitle = "Mitnahme & Lieferung";
            } elseif ($pickupEnabled && !$deliveryEnabled) {
                $orderTitle = "Mitnahme";
            } elseif (!$pickupEnabled && $deliveryEnabled) {
                $orderTitle = "Lieferung";
            } else {
                $orderTitle = "Online Bestellung";
            }
            
            // Prüfe ob das Restaurant geschlossen ist oder Bestellstopp
            $isRestaurantClosed = !$this->systemSettings['restaurant_open'];
            $isOrderingStopped = $this->systemSettings['restaurant_open'] && !$this->systemSettings['ordering_available'];
            
            if ($isOrderingStopped) {
                $orderText = "Unser Online-Bestellsystem ist derzeit geschlossen, da wir keine neuen Bestellungen mehr annehmen (2 Stunden vor Schließung). Bestellen Sie gerne telefonisch oder besuchen Sie uns direkt im Restaurant.";
                $buttonText = "Bestellstopp aktiv";
            } elseif ($isRestaurantClosed) {
                $orderText = "Unser Online-Bestellsystem ist derzeit geschlossen, da das Restaurant außerhalb der Öffnungszeiten ist. Bestellungen sind während unserer Öffnungszeiten möglich.";
                $buttonText = "Restaurant geschlossen";
            } else {
                $orderText = "Unser Online-Bestellsystem ist derzeit nicht verfügbar. Bestellen Sie gerne telefonisch unter 06021 25779.";
                $buttonText = "Online-Bestellung nicht verfügbar";
            }
            
            echo <<< EOT
                <div class="order-section">
                <div class="order-info">
                  <div class="order-text">
                    <h2>{$orderTitle}</h2>
                    <p>
                      {$orderText}
                    </p>
                    <div class="order-button disabled" title="{$buttonText}">{$buttonText}</div>
                  </div>
                  <div class="order-image">
                    <img src="public/assets/img/5.jpg" alt="Willkommensbild" />
                  </div>
                  </div>
                </div>
            EOT;
        }
        // @todo: Hinweise dynamisch einblenden (wenn Lieferung an, Hinweise an)
    }

    private function generateGalleryDisplay(){
        // Bilder aus der Datenbank abrufen mit JOIN und Sortierung
        $sql = "SELECT i.id, i.name, g.display_order, g.description
            FROM gallery g 
            INNER JOIN images i ON g.image_id = i.id 
            WHERE g.active = TRUE 
            ORDER BY g.display_order ASC";

        $result = $this->_database->query($sql);
        $images = $result->fetchAll(\PDO::FETCH_ASSOC);

        echo <<< EOT
        <div id="gallery" class="gallery-section">
            <div class="gallery-content">
                <div class="slideshow-wrapper">
                    <div class="slideshow-container">
                        <!-- Bilder werden dynamisch eingefügt -->
EOT;

        foreach ($images as $index => $image) {
            // SEO: Sprechender Alt-Text für Galerie-Bilder
            $altText = !empty($image['description'])
                ? htmlspecialchars($image['description'])
                : 'Ambiente im Restaurant Dionysos';
            echo <<<EOT
                <div class="slide fade">
                    <img src="public/assets/img/{$image['name']}" alt="{$altText}" loading="lazy" onclick="enlargeImage(this)">
                    <div class="caption">{$image['description']}</div>
                </div>
EOT;

        }

    echo <<<EOT
                <!-- Navigations-Buttons -->
                <button type="button" class="prev" onclick="changeSlide(-1)" aria-label="Vorheriges Bild">&#10094;</button>
                <button type="button" class="next" onclick="changeSlide(1)" aria-label="Nächstes Bild">&#10095;</button>
            </div>
            
            <!-- Indikatoren -->
            <div class="dots-container">
EOT;

    for ($i = 0; $i < count($images); $i++) {
        echo "<span class='dot' onclick='currentSlide($i)'></span>";
    }

    echo <<<EOT
            </div>
            
            <!-- Vergrößertes Bild Modal -->
            <div id="imageModal" class="modal" onclick="closeModal()">
                <img id="modalImage" class="modal-content">
            </div>
            </div>
            
            <div class="gallery-text">
                <h2>Unser Ambiente</h2>
                <p>Willkommen im DIONYSOS, wo sich traditionelle griechische Gastfreundschaft 
                mit modernem Ambiente vereint. In unseren gemütlichen Räumlichkeiten schaffen wir eine einladende 
                Atmosphäre, die zum Verweilen einlädt.
                    
                Genießen Sie nicht nur unsere ausgezeichnete Küche, sondern auch das stilvolle Ambiente unseres 
                Restaurants. Ob Sie einen romantischen Abend zu zweit, ein Geschäftsessen oder eine Familienfeier 
                planen - bei uns finden Sie den perfekten Rahmen für jeden Anlass.
                
                Lassen Sie sich von der Wärme unserer Einrichtung und dem aufmerksamen Service verwöhnen. 
                Unsere mediterranen Akzente und die authentische Atmosphäre machen jeden Besuch zu einem 
                besonderen Erlebnis.</p>
            </div>
        </div>        
        </div>
        <script src="public/assets/js/gallery-behaviour.js"></script>
EOT;
}

    private function generateApproachDisplay()
    {
        $mapContent = $this->cookieHandler->isAllowGoogle()
            ? '<iframe 
            src="https://www.google.com/maps/d/u/0/embed?mid=1PUapZmiIxbQRXtQ-yye2FHxICSXaDdr6&z=15" 
            width="100%" 
            height="450" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
           </iframe>'
            : '<div class="map-placeholder">
             <p>Bitte aktivieren Sie Google Maps Cookies in den Cookie-Einstellungen, um die Karte anzuzeigen.</p>
             <form method="POST" action="">
            <input type="hidden" name="set_cookie_preferences" value="1">
            <button type="submit" class="cookie-settings-link">Cookie-Einstellungen ändern</button>
            </form>
           </div>';
        if ($_POST['set_cookie_preferences'] ?? false) {
            $this->cookieHandler->generateCookieSettings();
        }

        echo <<< EOT
    <div id="contact" class="approach-section">
        <div class="approach-content">
            <div class="approach-text">
                <h1>So finden Sie uns</h1>
                <p>Besuchen Sie uns am Floßhafen 27, 63739 Aschaffenburg</p>
            </div>
            <div class="map-container">
                <style>
                    .map-placeholder {
                        background-color: #f5f5f5;
                        padding: 20px;
                        text-align: center;
                        height: 450px;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        border: 1px solid #ddd;
                    }
                    
                    .cookie-settings-link {
                        display: inline-block;
                        margin-top: 15px;
                        padding: 10px 20px;
                        background-color: #ffab66;
                        color: #000;
                        text-decoration: none;
                        border-radius: 4px;
                        transition: background-color 0.3s;
                    }
                    
                    .cookie-settings-link:hover {
                        background-color: #ff9933;
                    }
                </style>
                {$mapContent}
            </div>
        </div>
    </div>
    EOT;
    }
}

// CSS für deaktivierte Systeme
echo <<<'DISABLED_CSS'
<style>
    .disabled-link {
        color: #999 !important;
        cursor: not-allowed !important;
        text-decoration: none !important;
        pointer-events: none !important;
    }

    .intro-reservation.disabled {
        background: #ccc !important;
        color: #666 !important;
        cursor: not-allowed !important;
        text-decoration: none !important;
        pointer-events: none !important;
    }

    .order-button.disabled {
        background: #ccc !important;
        color: #666 !important;
        cursor: not-allowed !important;
        text-decoration: none !important;
        pointer-events: none !important;
        display: inline-block;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: bold;
    }
</style>
DISABLED_CSS;

Home::main();