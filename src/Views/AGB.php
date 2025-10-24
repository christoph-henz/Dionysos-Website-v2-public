<?php

namespace Dionysosv2\Views;
use Dionysosv2\Controller\MenuBuilder;
use Exception;

class AGB extends Page
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
            $page = new AGB();
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
        $this->generateImpressum();
        //$this->generateImpressum2();
        echo '</div></div>';
    }

    private function generateImpressum()
    {
        echo <<< EOT
            <div class="data-container">
                <h1>AGB</h1>
                <div class="data-contents">
                <div class="section">
                    <h2>1. Geltungsbereich</h2>
                    <p>Diese Allgemeinen Geschäftsbedingungen (AGB) gelten für alle Bestellungen, Reservierungen und sonstigen Dienstleistungen, die über unsere Website erfolgen. Mit der Nutzung unserer Website erklären Sie sich mit diesen AGB einverstanden.</p>
                </div>
                <hr>
            
                <div class="section">
                    <h2>2. Vertragspartner</h2>
                    <p>Der Vertrag kommt zustande zwischen:</p>
                    <ul>
                        <li>Restaurant Dionysos</li>
                        <li>Floßhafen 27</li>
                        <li>63739 Aschaffenburg</li>
                        <li>06021 25779</li>
                        <li>info@dionysos-aburg.de</li>
                    </ul>
                    <p>(im Folgenden "Restaurant" genannt) und dem Kunden, der über die Website eine Bestellung oder Reservierung aufgibt.</p>
                </div>
                
                <hr>
            
                <div class="section">
                    <h2>3. Bestellungen</h2>
                    <h3>3.1 Online-Bestellung</h3>
                    <ul>
                        <li>Der Kunde kann über die Website Speisen und Getränke zur Lieferung oder Abholung bestellen.</li>
                        <li>Die im Online-Shop angegebenen Preise enthalten die gesetzliche Mehrwertsteuer.</li>
                        <li>Bestellungen gelten als verbindlich, sobald sie vom Restaurant bestätigt wurden.</li>
                        <li>Das Restaurant behält sich das Recht vor, Bestellungen in Ausnahmefällen abzulehnen (z.B. bei Lieferengpässen oder ungewöhnlich hohen Bestellmengen).</li>
                    </ul>
            
                    <h3>3.2 Lieferung</h3>
                    <ul>
                        <li>Die Lieferung erfolgt an die vom Kunden angegebene Adresse.</li>
                        <li>Lieferkosten werden dem Kunden vor Abschluss der Bestellung angezeigt.</li>
                        <li>Das Restaurant übernimmt keine Haftung für verspätete Lieferungen aufgrund höherer Gewalt, Verkehrslage oder technischer Probleme.</li>
                    </ul>
            
                    <h3>3.3 Abholung</h3>
                    <ul>
                        <li>Bei der Bestellung zur Abholung kann der Kunde einen Abholzeitpunkt wählen.</li>
                        <li>Der Kunde ist verpflichtet, die bestellten Speisen innerhalb des angegebenen Zeitraums abzuholen.</li>
                    </ul>
                </div>
                
                <hr>
                
                <div class="section">
                    <h2>4. Reservierungen</h2>
                    <h3>4.1 Reservierungsanfrage</h3>
                    <ul>
                        <li>Reservierungen können über das auf der Website bereitgestellte Reservierungssystem vorgenommen werden.</li>
                        <li>Reservierungen für den Folgetag können nur bis 22:00 Uhr abgeschlossen werden</li>
                        <li>Der Kunde erhält eine Bestätigung der Reservierung per E-Mail.</li>
                        <li>Das Restaurant behält sich das Recht vor, Reservierungen in Ausnahmefällen abzulehnen.</li>
                    </ul>
            
                    <h3>4.2 Stornierung von Reservierungen</h3>
                    <ul> 
                        <li>Stornierungen sind bis 48 Stunden vor dem Reservierungszeitpunkt kostenfrei möglich.</li>
                        <li>Für Reservierungen von Gruppen ab 10 Personen wird im Voraus eine Kaution in Höhe von 10 € pro Person erhoben.</li> 
                        <li>Sollte die Reservierung nicht mindestens 24 Stunden vor dem Reservierungszeitpunkt abgesagt werden oder die Gruppe ohne Absage nicht erscheinen, wird die geleistete Kaution vollständig einbehalten.</li>
                        <li>Das Restaurant behält sich außerdem das Recht vor, den Betrag von 10 € pro Person für die Anzahl an Personen einzubehalten, die weniger erscheinen als ursprünglich reserviert wurden.</li>
                    </ul>
            
                    <h3>4.3 No-Show-Regel</h3>
                    <ul>
                        <li>Sollte der Kunde ohne rechtzeitige Stornierung nicht zur Reservierung erscheinen, behält sich das Restaurant vor, zukünftige Reservierungen abzulehnen.</li>
                    </ul>
                </div>
                
                <hr>
            
                <div class="section">
                    <h2>5. Widerrufsrecht</h2>
                    <p>Gemäß § 312g Abs. 2 Nr. 9 BGB besteht kein Widerrufsrecht für die Lieferung von Speisen und Getränken, die schnell verderben oder deren Verfallsdatum überschritten würde.</p>
                </div>
                
                <hr>
            
                <div class="section">
                    <h2>6. Zahlung</h2>
                    <h3>6.1 Zahlungsmethoden</h3>
                    <ul>
                        <li>Zahlungen können bei Abholung per Bankkarte sowie mit Bargeld und bei Lieferung in bar erfolgen.</li>
                        <li>Das Restaurant behält sich das Recht vor, bestimmte Zahlungsmethoden auszuschließen.</li>
                    </ul>
            
                    <h3>6.2 Fälligkeit</h3>
                    <ul>
                        <li>Der Kaufpreis ist mit Vertragsschluss sofort fällig.</li>
                        <li>Bei Zahlungsverzug behält sich das Restaurant das Recht vor, rechtliche Schritte einzuleiten.</li>
                    </ul>
                </div>
                
                <hr>
            
                <div class="section">
                    <h2>7. Haftung</h2>
                    <p>Das Restaurant haftet nur für Vorsatz und grobe Fahrlässigkeit. Für leichte Fahrlässigkeit haftet das Restaurant nur bei der Verletzung wesentlicher Vertragspflichten. Das Restaurant haftet nicht für technische Probleme, die zur Nichterreichbarkeit der Website oder Verzögerungen bei der Bearbeitung von Bestellungen oder Reservierungen führen.</p>
                </div>
                
                <hr>
                
                <div class="section">
                    <h2>8. Datenschutz</h2>
                    <p>Informationen zur Erhebung, Verarbeitung und Nutzung personenbezogener Daten finden Sie in unserer <a style="color: coral" href="Datenschutz.php">Datenschutzerklärung</a>.</p>
                </div>
                
                <hr>
                
                <div class="section">
                    <h2>9. Google Maps</h2>
                    <p>Diese Website verwendet Google Maps, um den Standort des Restaurants anzuzeigen und eine Routenplanung zu ermöglichen. Durch die Nutzung von Google Maps werden Informationen über die Nutzung der Kartenfunktion durch den Kunden an Google übermittelt. Weitere Informationen dazu finden Sie in der Datenschutzerklärung.</p>
                </div>
                
                <hr>
                
                <div class="section">
                    <h2>10. Gerichtsstand</h2>
                    <p>Für alle Streitigkeiten aus Verträgen, die unter Einbeziehung dieser AGB geschlossen wurden, gilt der Sitz des Restaurants als Gerichtsstand, sofern der Kunde Kaufmann, eine juristische Person des öffentlichen Rechts oder ein öffentlich-rechtliches Sondervermögen ist.</p>
                </div>
                
                <hr>
                
                <div class="section">
                    <h2>11. Salvatorische Klausel</h2>
                    <p>Sollten einzelne Bestimmungen dieser AGB unwirksam sein oder werden, so bleibt die Wirksamkeit der übrigen Bestimmungen davon unberührt. Anstelle der unwirksamen Bestimmung treten die gesetzlichen Vorschriften.</p>
                </div>
                </div>
            </div>
        EOT;

    }
}

AGB::main();