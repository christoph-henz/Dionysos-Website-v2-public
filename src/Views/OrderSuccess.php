<?php

namespace Dionysosv2\Views;

use Exception;

class OrderSuccess extends Page
{
    /**
     * Instantiates members (to be defined above).
     * Calls the constructor of the parent i.e. page class.
     * So, the database connection is established.
     */
    protected function __construct()
    {
        parent::__construct();
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
     */
    public static function main(): void
    {
        try {
            $page = new OrderSuccess();
            $page->processReceivedData();
            $page->generateView();
        } catch (Exception $e) {
            header("Content-type: text/html; charset=UTF-8");
            echo $e->getMessage();
        }
    }

    /**
     * Processes the data that comes via GET or POST.
     */
    protected function processReceivedData(): void
    {
        parent::processReceivedData();
        
        // Prüfen ob eine gültige Bestellung vorliegt
        if (!isset($_SESSION['order_success']) || !$_SESSION['order_success']) {
            // Keine gültige Bestellung gefunden - Weiterleitung zum Bestellformular
            if (!headers_sent()) {
                header('Location: /order');
                exit;
            } else {
                echo '<script>window.location.href = "/order";</script>';
                exit;
            }
        }
    }

    /**
     * Outputs additional meta data in the HTML head section.
     */
    protected function additionalMetaData(): void
    {
        //Links for css or js
        echo <<< EOT
            <link rel="stylesheet" type="text/css" href="../public/assets/css/home.css"/>
            <link rel="stylesheet" type="text/css" href="../public/assets/css/order-menu.css"/>
            <script src="../public/assets/js/home-behavior.js"></script> 
        EOT;
    }

    /**
     * First the required data is fetched and then the HTML is
     * assembled for output.
     */
    protected function generateView(): void
    {
        $this->generatePageHeader('Bestellung erfolgreich');
        $this->generateMainBody();
        $this->generatePageFooter();
    }
    private function generateMainBody(): void
    {
        $this->generateIntroDisplay();
        $this->generateBody();
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

    private function generateBody(): void
    {
        $orderId = $_SESSION['order_id'] ?? null;
        $isSuccess = isset($_SESSION['order_success']) && $_SESSION['order_success'];
        
        echo '<div class="main-container">';
        
        if ($isSuccess && $orderId) {
            echo '<div class="success-container">';
            echo '<div class="success-icon">✅</div>';
            echo '<h1>Bestellung erfolgreich aufgegeben!</h1>';
            echo '<p class="order-number">Bestellnummer: <strong>#' . htmlspecialchars($orderId) . '</strong></p>';
            echo '<div class="success-message">';
            echo '<p>Vielen Dank für Ihre Bestellung! Wir haben Ihre Bestellung erhalten und werden sie so schnell wie möglich bearbeiten.</p>';
            echo '<p><strong>Was passiert jetzt?</strong></p>';
            echo '<ul>';
            echo '<li>Wir prüfen Ihre Bestellung und bestätigen sie in Kürze</li>';
            echo '<li>Sie erhalten eine Benachrichtigung per E-Mail oder Telefon</li>';
            echo '<li>Bei Lieferungen melden wir uns vor der Auslieferung</li>';
            echo '<li>Bei Abholungen können Sie nach unserer Bestätigung das Essen abholen</li>';
            echo '</ul>';
            echo '</div>';
            echo '<div class="action-buttons">';
            echo '<a href="/" class="btn btn-primary">Zurück zur Startseite</a>';
            echo '<a href="/order" class="btn btn-secondary">Neue Bestellung</a>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="error-container">';
            echo '<div class="error-icon">❌</div>';
            echo '<h1>Bestellung nicht gefunden</h1>';
            echo '<p>Es konnte keine gültige Bestellung gefunden werden.</p>';
            echo '<a href="/order" class="btn btn-primary">Neue Bestellung aufgeben</a>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Session-Variablen nach dem Anzeigen löschen
        unset($_SESSION['order_success'], $_SESSION['order_id']);
        
        $this->generateStyles();
    }

    private function generateStyles(): void
    {
        echo <<<EOT
        <style>
            .main-container {
                min-height: 80vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
                max-width: 1200px;
                margin: 0 auto;
            }

            .success-container, .error-container {
                max-width: 600px;
                width: 100%;
                text-align: center;
                background: white;
                padding: 3rem;
                border-radius: 12px;
                box-shadow: 0 4px 16px rgba(0,0,0,0.1);
                margin: 0 auto;
            }

            .success-icon, .error-icon {
                font-size: 4rem;
                margin-bottom: 1rem;
            }

            .success-container h1 {
                color: #2e7d32;
                margin-bottom: 1rem;
                font-size: 2rem;
            }

            .error-container h1 {
                color: #d32f2f;
                margin-bottom: 1rem;
                font-size: 2rem;
            }

            .order-number {
                font-size: 1.2rem;
                color: #666;
                margin-bottom: 2rem;
                padding: 1rem;
                background: #f5f5f5;
                border-radius: 8px;
                border: 2px solid #ffab66;
            }

            .success-message {
                text-align: left;
                margin: 2rem 0;
                padding: 1.5rem;
                background: #e8f5e8;
                border-radius: 8px;
                border-left: 4px solid #4caf50;
            }

            .success-message p {
                margin-bottom: 1rem;
                line-height: 1.6;
            }

            .success-message ul {
                margin: 1rem 0;
                padding-left: 1.5rem;
                line-height: 1.6;
            }

            .success-message li {
                margin-bottom: 0.5rem;
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
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                transition: all 0.2s;
                text-align: center;
                min-width: 150px;
            }

            .btn-primary {
                background: #ffab66;
                color: white;
            }

            .btn-primary:hover {
                background: #ff9240;
                transform: translateY(-1px);
            }

            .btn-secondary {
                background: #f5f5f5;
                color: #333;
                border: 2px solid #ddd;
            }

            .btn-secondary:hover {
                background: #e0e0e0;
                border-color: #ccc;
                transform: translateY(-1px);
            }

            /* Mobile Responsive Design */
            @media (max-width: 768px) {
                .main-container {
                    padding: 1rem 0.5rem;
                    min-height: 70vh;
                }

                .success-container, .error-container {
                    padding: 2rem 1.5rem;
                    border-radius: 8px;
                    width: 100%;
                    max-width: 100%;
                    margin: 0;
                    box-sizing: border-box;
                }

                .success-icon, .error-icon {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                }

                .success-container h1,
                .error-container h1 {
                    font-size: 1.5rem;
                    margin-bottom: 1rem;
                    line-height: 1.3;
                }

                .order-number {
                    font-size: 1.1rem;
                    padding: 0.8rem;
                    margin-bottom: 1.5rem;
                }

                .success-message {
                    padding: 1rem;
                    margin: 1.5rem 0;
                    text-align: left;
                }

                .success-message p {
                    font-size: 0.95rem;
                    margin-bottom: 0.8rem;
                }

                .success-message ul {
                    padding-left: 1.2rem;
                    font-size: 0.9rem;
                }

                .success-message li {
                    margin-bottom: 0.4rem;
                }

                .action-buttons {
                    flex-direction: column;
                    align-items: center;
                    gap: 0.8rem;
                    margin-top: 1.5rem;
                }

                .btn {
                    width: 100%;
                    max-width: 250px;
                    padding: 0.8rem 1rem;
                    font-size: 0.95rem;
                }
            }

            /* Extra small screens */
            @media (max-width: 480px) {
                .main-container {
                    padding: 0.5rem 0.25rem;
                }

                .success-container, .error-container {
                    padding: 1.5rem 1rem;
                }

                .success-icon, .error-icon {
                    font-size: 2.5rem;
                }

                .success-container h1,
                .error-container h1 {
                    font-size: 1.3rem;
                }

                .order-number {
                    font-size: 1rem;
                    padding: 0.7rem;
                }

                .success-message {
                    padding: 0.8rem;
                }

                .success-message p,
                .success-message ul {
                    font-size: 0.9rem;
                }

                .btn {
                    padding: 0.7rem 0.8rem;
                    font-size: 0.9rem;
                }
            }

            /* Landscape mobile orientation */
            @media (max-width: 768px) and (orientation: landscape) {
                .main-container {
                    min-height: 90vh;
                    padding: 1rem 0.5rem;
                }

                .success-container, .error-container {
                    padding: 1.5rem;
                }

                .success-message {
                    margin: 1rem 0;
                }

                .action-buttons {
                    flex-direction: row;
                    justify-content: center;
                }

                .btn {
                    width: auto;
                    min-width: 120px;
                }
            }
        </style>
        EOT;
    }
}

OrderSuccess::main();
