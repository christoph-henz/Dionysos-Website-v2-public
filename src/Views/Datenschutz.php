<?php

namespace Dionysosv2\Views;
use Dionysosv2\Controller\MenuBuilder;

class Datenschutz extends Page
{
    /**
     * Properties
     */

    /**
     * Instantiates members (to be defined above).
     * Calls the constructor of the parent i.e. page class.
     * So, the database connection is established.
     * @throws Exception
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
            $page = new Datenschutz();
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
        $this->generatePageHeader('Datenschutz'); //to do: set optional parameters

        //$this->generateNav();
        $this->generateMainBody();
        $this->generatePageFooter();
    }

    private function generateMainBody(){
        $this->generateIntroDisplay();
    }

    protected function additionalMetaData(): void
    {
        //Links for css or js
        echo <<< EOT
            <link rel="stylesheet" type="text/css" href="public/assets/css/home.css"/>
            <link rel="stylesheet" type="text/css" href="public/assets/css/law.css"/>
            <script src="public/assets/js/home-behavior.js"></script> 
        EOT;
    }

    private function generateIntroDisplay() : void{
        echo <<< EOT
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
        EOT;
        $this->generateDatenschutz();
        echo '</div></div>';
    }

    private function generateDatenschutz()
    {
        echo <<< EOT
            <div class="data-container">
                <h1>Datenschutz</h1>
                <div class="data-contents">
                    <h2>1. Allgemeine Hinweise</h2>
                    <p>
                        Der Schutz Ihrer persönlichen Daten ist uns ein wichtiges Anliegen. In dieser Datenschutzerklärung informieren wir Sie darüber, welche Daten wir erheben, wie wir diese nutzen und welche Rechte Ihnen in Bezug auf Ihre Daten zustehen.
                    </p>
                    <hr>
                    <h2>2. Verantwortliche Stelle</h2>
                    <p>
                        Verantwortlich für die Datenverarbeitung auf dieser Website ist:<br>
                        <strong>Restaurant Dionysos</strong><br>
                        Ioannis Gkogkas<br>
                        Floßhafen 27<br>
                        63739 Aschaffenburg<br>
                        E-Mail: <a href="mailto:info@dionysos-aburg.de">info@dionysos-aburg.de</a>
                    </p>
                    <hr>
                    <h2>3. Datenerfassung auf unserer Website</h2>
                    <h3>3.1 Bestell- und Liefersystem</h3>
                    <p>
                        Im Rahmen unseres Online-Bestell- und Liefersystems erheben wir folgende personenbezogene Daten:
                    </p>
                    <ul>
                        <li>Name</li>
                        <li>Adresse (für die Lieferung)</li>
                        <li>E-Mail-Adresse (für Bestellbestätigungen und Rückfragen)</li>
                        <li>Telefonnummer (für Rückfragen zur Bestellung)</li>
                        <li>Bestellinformationen (Details der Bestellung)</li>
                    </ul>
                    <p>
                        Die Datenverarbeitung erfolgt, um Ihre Bestellung zu bearbeiten und auszuliefern. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO (Verarbeitung zur Erfüllung eines Vertrags).
                    </p>
                    <h3>3.2 Reservierungssystem</h3>
                    <p>
                        Wenn Sie über unser Reservierungssystem einen Tisch reservieren, verarbeiten wir folgende personenbezogene Daten:
                    </p>
                    <ul>
                        <li>Name</li>
                        <li>E-Mail-Adresse</li>
                        <li>Telefonnummer</li>
                        <li>Reservierungsdetails (Datum, Uhrzeit, Anzahl der Gäste)</li>
                    </ul>
                    <p>
                        Diese Daten werden ausschließlich zur Bearbeitung Ihrer Reservierung genutzt. Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO.
                    </p>
                    <hr>
                    <h2>4. Nutzung von Google Maps</h2>
                    <p>
                        Diese Website nutzt den Kartendienst Google Maps. Anbieter ist die Google Ireland Limited („Google“), Gordon House, Barrow Street, Dublin 4, Irland.
                    </p>
                    <p>
                        Zur Nutzung der Funktionen von Google Maps ist es notwendig, Ihre IP-Adresse zu speichern. Diese Informationen werden in der Regel an einen Server von Google in den USA übertragen und dort gespeichert. Wir haben keinen Einfluss auf diese Datenübertragung.
                    </p>
                    <p>
                        Die Nutzung von Google Maps erfolgt im Interesse einer ansprechenden Darstellung unseres Online-Angebots und einer leichten Auffindbarkeit der von uns auf der Website angegebenen Orte. Dies stellt ein berechtigtes Interesse im Sinne von Art. 6 Abs. 1 lit. f DSGVO dar.
                    </p>
                    <p>
                        Weitere Informationen zum Umgang mit Nutzerdaten finden Sie in der Datenschutzerklärung von Google: <a href="https://policies.google.com/privacy" target="_blank">https://policies.google.com/privacy</a>.
                    </p>
                    <hr>
                    <h2>5. Speicherdauer der Daten</h2>
                    <p>
                        Wir speichern personenbezogene Daten nur so lange, wie dies für die Bearbeitung Ihrer Anfrage, Bestellung oder Reservierung erforderlich ist. Sofern keine gesetzlichen Aufbewahrungsfristen bestehen, werden die Daten nach Erledigung der Anfrage oder vollständiger Vertragserfüllung gelöscht.
                    </p>
                    <hr>
                    <h2>6. Weitergabe von Daten</h2>
                    <p>
                        Eine Weitergabe Ihrer persönlichen Daten an Dritte erfolgt nur, wenn dies zur Vertragsabwicklung notwendig ist (z. B. an Lieferdienste), Sie ausdrücklich eingewilligt haben oder eine gesetzliche Verpflichtung besteht.
                    </p>
                    <hr>
                    <h2>7. Ihre Rechte</h2>
                    <p>Sie haben jederzeit das Recht auf:</p>
                    <ul>
                        <li>Auskunft über Ihre gespeicherten personenbezogenen Daten</li>
                        <li>Berichtigung unrichtiger Daten</li>
                        <li>Löschung Ihrer Daten</li>
                        <li>Einschränkung der Verarbeitung</li>
                        <li>Datenübertragbarkeit</li>
                        <li>Widerspruch gegen die Verarbeitung</li>
                    </ul>
                    <p>
                        Um eines dieser Rechte geltend zu machen, wenden Sie sich bitte an uns unter: <a href="mailto:info@dionysos-aburg.de">info@dionysos-aburg.de</a>.
                    </p>
                    <hr>
                    <h2>8. Externes Hosting</h2>
                    <p>
                        Unsere Website wird bei einem externen Dienstleister (Hoster) gehostet. Die personenbezogenen Daten, die auf dieser Website erfasst werden, werden auf den Servern des Hosters gespeichert. Rechtsgrundlage für die Datenverarbeitung ist Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse).
                    </p>
                    <hr>
                    <h2>9. Änderungen dieser Datenschutzerklärung</h2>
                    <p>
                        Wir behalten uns das Recht vor, diese Datenschutzerklärung jederzeit zu ändern, um sie an aktuelle rechtliche Anforderungen oder Änderungen unserer Dienstleistungen anzupassen. Die neue Datenschutzerklärung gilt dann bei Ihrem nächsten Besuch.
                    </p>
                </div>
            </div>
        EOT;

    }
}

Datenschutz::main();