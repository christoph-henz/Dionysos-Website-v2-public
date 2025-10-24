<?php

namespace Dionysosv2\Views;
use Exception;
class Impressum extends Page
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
            $page = new Impressum();
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
        $this->generatePageHeader('Impressum'); //to do: set optional parameters

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
                <h1>Impressum</h1>
                <div class="data-contents">
                    <p>
                        <strong>Restaurant Dionysos</strong><br>
                        Ioannis Gkogkas<br>
                        Floßhafen 27<br>
                        63739 Aschaffenburg<br>
                    </p>
            
                    <!--hr>
            
                    <h3>Vertreten durch:</h3>
                    <p>Kalin Yakimov</p-->
            
                    <hr>
            
                    <h3>Kontakt:</h3>
                    <p>
                        Telefon: +49 06021 25779<br>
                        E-Mail: <a href="mailto:info@dionysos-aburg.de">info@dionysos-aburg.de</a><br>
                        Website: <a href="https://www.dionysos-aburg.de">www.dionysos-aburg.de</a>
                    </p>
            
                    <hr>
            
                    <h3>Umsatzsteuer-ID:</h3>
                    <p>
                        Umsatzsteuer-Identifikationsnummer gemäß §27 a Umsatzsteuergesetz: DE 82 843 719 062
                    </p>
            
                    <hr>
            
                    <h3>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:</h3>
                    <p>
                        Ioannis Gkogkas<br>
                        Floßhafen 27<br>
                        63739 Aschaffenburg
                    </p>
            
                    <hr>
            
                    <h2>Haftungsausschluss (Disclaimer)</h2>
            
                    <h3>Haftung für Inhalte</h3>
                    <p>
                        Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich.
                        Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen
                        oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.
                        Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt.
                        Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich.
                        Bei Bekanntwerden von entsprechenden Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.
                    </p>
            
                    <hr>
            
                    <h3>Haftung für Links</h3>
                    <p>
                        Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben.
                        Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen.
                        Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.
                        Die verlinkten Seiten wurden zum Zeitpunkt der Verlinkung auf mögliche Rechtsverstöße überprüft.
                        Rechtswidrige Inhalte waren zum Zeitpunkt der Verlinkung nicht erkennbar.
                        Eine permanente inhaltliche Kontrolle der verlinkten Seiten ist jedoch ohne konkrete Anhaltspunkte einer Rechtsverletzung nicht zumutbar.
                        Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links umgehend entfernen.
                    </p>
            
                    <hr>
            
                    <h3>Urheberrecht</h3>
                    <p>
                        Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht.
                        Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes
                        bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers.
                        Downloads und Kopien dieser Seite sind nur für den privaten, nicht kommerziellen Gebrauch gestattet.
                        Soweit die Inhalte auf dieser Seite nicht vom Betreiber erstellt wurden, werden die Urheberrechte Dritter beachtet.
                        Insbesondere werden Inhalte Dritter als solche gekennzeichnet.
                        Sollten Sie trotzdem auf eine Urheberrechtsverletzung aufmerksam werden, bitten wir um einen entsprechenden Hinweis.
                        Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Inhalte umgehend entfernen.
                    </p>
            
                    <hr>
            
                    <h3>Streitschlichtung</h3>
                    <p>
                        Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:
                        <a href="https://ec.europa.eu/consumers/odr" target="_blank">https://ec.europa.eu/consumers/odr</a>.<br>
                        Unsere E-Mail-Adresse finden Sie oben im Impressum.<br>
                        Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.
                    </p>
                </div>
            </div>
        EOT;

    }
}

Impressum::main();