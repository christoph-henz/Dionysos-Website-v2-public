<?php declare(strict_types=1);
// UTF-8 marker äöüÄÖÜß€
namespace Dionysosv2\Views;

abstract class Page
{
    // --- ATTRIBUTES ---

    protected ?\PDO $_database = null;
    protected $isLocal;

    protected CookieHandler $cookieHandler;


    // --- OPERATIONS ---

    /**
     * Connects to DB and stores
     * the connection in member $_database.
     * Needs name of DB, user, password.
     */

    protected function __construct()
    {
        error_reporting(E_ALL);
        // Session vor dem CookieHandler starten
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->cookieHandler = new CookieHandler();

        // Umgebung prüfen
        $this->isLocal = ($_SERVER['HTTP_HOST'] === 'localhost');

        if ($this->isLocal) {
            // ✅ Lokale Umgebung: SQLite verwenden
            $sqlitePath = __DIR__ . '/../../database.db'; // Pfad anpassen
            try {
                $this->_database = new \PDO("sqlite:$sqlitePath");
                $this->_database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->_database->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                die("Fehler beim Verbinden mit SQLite: " . $e->getMessage());
            }
        } else {
            // ✅ Live-Umgebung: MySQL/MariaDB verwenden (mit PDO)
            $dbHost = "db************.hosting-data.io";
            $dbUser = "dbu***********";
            $dbPassword = "*************************";
            $dbName = "dbs**********";

            try {
                // MySQL-Verbindung mit PDO erstellen
                $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
                $this->_database = new \PDO($dsn, $dbUser, $dbPassword);
                
                // Gleiche Einstellungen wie bei SQLite
                $this->_database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->_database->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                
                // MySQL-spezifische Einstellung: Emulation von Prepared Statements ausschalten
                $this->_database->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            } catch (\PDOException $e) {
                die("Fehler beim Verbinden mit MySQL: " . $e->getMessage());
            }
        }
    }

    /**
     * Closes the DB connection and cleans up
     */
    public function __destruct()
    {
        // PDO schließt die Verbindung automatisch
        $this->_database = null; // Verbindung explizit freigeben
    }

    /**
     * Generates the header section of the page.
     * i.e. starting from the content type up to the body-tag.
     * Takes care that all strings passed from outside
     * are converted to safe HTML by htmlspecialchars.
     *
     * @param string $title $title is the text to be used as title of the page
     * @param string $jsFile path to a java script file to be included, default is "" i.e. no java script file
     * @param bool $autoreload  true: auto reload the page every 5 s, false: not auto reload
     * @return void
     */
    protected function generatePageHeader(string $title = "", string $jsFile = "", bool $autoreload = false):void
    {
        $title = htmlspecialchars($title);
        header("Content-type: text/html; charset=UTF-8");

        // to do: handle all parameters
        // to do: output common beginning of HTML code

        echo <<< HTML
            <!DOCTYPE html>
            <html lang="de">
                <head>
                <title>$title</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta name="description" content="Griechisches Restaurant Dionysos in Aschaffenburg. Reservieren, bestellen und Speisekarte online ansehen. Authentische griechische Küche und gemütliches Ambiente.">
                <meta name="keywords" content="Restaurant, Dionysos, Aschaffenburg, griechisch, Reservierung, Bestellung, Speisekarte, Essen, Taverna, Terrasse, Main">
                <meta property="og:title" content="Restaurant Dionysos Aschaffenburg">
                <meta property="og:description" content="Reservieren, bestellen und genießen Sie griechische Spezialitäten im Dionysos Aschaffenburg.">
                <meta property="og:type" content="restaurant">
                <meta property="og:site_name" content="Restaurant Dionysos">
                <link rel="stylesheet" type="text/css" href="../public/assets/css/page-components.css"/>                 
        HTML;
        $this->additionalMetaData();

        //$this->generateBanner("Am 06.01.2025 hat unsere Gaststätte ab 17.30 Uhr für Sie geöffnet");
        echo <<< HTML
                </head>
        HTML;
        echo $this->cookieHandler->generateCookieBanner();

        echo "<body>";

    }

    /**
     * Optional method to be implemented by child classes to add
     * additional metadata to the header
     */
    protected function additionalMetaData(): void
    {
        // Default implementation is empty
    }

    protected function generateBanner(string $text)
    {
        echo <<<EOT
    <style>
        #Timebanner {
            position: fixed;
            top: 0;
            width: 100%;
            height: 10%;
            background-color: #111111;
            opacity: 0.8;
            color: gold;
            text-align: center;
            font-size: 40px;
            padding: 30px;
            z-index: 1;
            transform: translateX(100%);
            transition: transform 0.5s ease-in-out;
        }

        #closeButton {
            background: none;
            border: none;
            color: goldenrod;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 10px;
        }

        #closeButton:hover {
            color: red;
        }
    </style>
    <div id="Timebanner">
        <p>{$text}<button id="closeButton" onclick="closeBanner()">X</button></p>
    </div>
    <script>
        // Banner anzeigen
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('Timebanner').style.transform = 'translateX(0)';
            }, 100); // Verzögerung, um sicherzustellen, dass die Animation funktioniert
        });

        // Banner schließen
        function closeBanner() {
            document.getElementById('Timebanner').style.transform = 'translateX(100%)';
        }
    </script>
    EOT;
    }

    /**
     * Outputs the end of the HTML-file i.e. </body> etc.
     * @return void
     */
    protected function generatePageFooter():void
    {
        echo <<< HERE
                <footer class="main-footer">
                    <div class="footer-content">
                        <div class="footer-section">
                            <h3>Anschrift</h3>
                            <p>Restaurant Dionysos</p>
                            <p>Floßhafen 27</p>
                            <p>63739 Aschaffenburg</p>
                        </div>
                        
                        <div class="footer-section">
                            <h3>Öffnungszeiten</h3>
                            <p>Di - Sa: 17:30 - 23:00</p>
                            <p>So: 11:30 - 22:00</p>
                            <p>Montag Ruhetag</p>
                        </div>
                        
                        <div class="footer-section">
                            <h3>Kontakt</h3>
                            <p>Tel: 06021 25779</p>
                            <p>E-Mail: info@dionysos-aburg.de</p>
                        </div>
                        
                        <div class="footer-section">
                            <h3>Rechtliches</h3>
                            <p><a href="/impressum">Impressum</a></p>
                            <p><a href="/datenschutz">Datenschutz</a></p>
                            <p><a href="/agb">AGB</a></p>
                            <form method="POST" action="">
                            <input type="hidden" name="set_cookie_preferences" value="1">
                            <button type="submit" class="cookie-settings-link">Cookie-Einstellungen ändern</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="footer-bottom">
                        <div class="copyright">
                            <p>&copy; {$this->getCurrentYear()} Christoph Henz. Alle Rechte vorbehalten.</p>
                        </div>
                        <div class="social-media">
                            <a href="https://www.facebook.com" target="_blank"><img src="../public/assets/img/facebook.svg" alt="Facebook"></a>
                            <a href="https://www.instagram.com/dionysos_aburg/?hl=de" target="_blank"><img src="../public/assets/img/instagram.svg" alt="Instagram"></a>
                        </div>
                    </div>
                </footer>
             </main>
            </body>
        HERE;
        if ($_POST['set_cookie_preferences'] ?? false) {
            $this->cookieHandler->generateCookieSettings();
        }
    }

    private function getCurrentYear(): string
    {
        return date('Y');
    }

    /**
     * Processes the data that comes in via GET or POST.
     * If every derived page is supposed to do something common
     * with submitted data do it here.
     * E.g. checking the settings of PHP that
     * influence passing the parameters (e.g. magic_quotes).
     * @return void
     */
    protected function processReceivedData():void
    {
        if (isset($_POST[CookieHandler::ALLOW_ORDER_KEY]) || isset($_POST[CookieHandler::ALLOW_GOOGLE_KEY])) {
            // Cookie-Einstellungen speichern
            $this->cookieHandler->setAskedBefore(true);
            $this->cookieHandler->setAllowOrder(isset($_POST[CookieHandler::ALLOW_ORDER_KEY]));
            $this->cookieHandler->setAllowGoogle(isset($_POST[CookieHandler::ALLOW_GOOGLE_KEY]));

            // In Session speichern
            $_SESSION['cookie_settings_saved'] = true;

            // Seite neu laden um das Banner zu entfernen
            $uri = $_SERVER['REQUEST_URI'];
            header("Location: $uri");
            exit();
        }
    }

    private function generateCookiesBanner() : string
    {
        return "public/assets/img/cookies.svg";
    }
}
