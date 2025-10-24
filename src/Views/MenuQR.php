<?php

namespace Dionysosv2\Views;

use Dionysosv2\Controller\MenuBuilder;
use Exception;

class MenuQR extends Page
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
            $page = new MenuQR();
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
    }

    /**
     * Outputs additional meta data in the HTML head section.
     */
    protected function additionalMetaData(): void
    {
        //Links for css or js
        echo <<< EOT
            <link rel="stylesheet" type="text/css" href="../public/assets/css/home.css"/>
            <link rel="stylesheet" type="text/css" href="../public/assets/css/menu.css"/>
            <script src="../public/assets/js/home-behavior.js"></script> 
        EOT;
    }

    /**
     * First the required data is fetched and then the HTML is
     * assembled for output.
     */
    protected function generateView(): void
    {
        $this->generatePageHeader('Speisekarte');
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
        echo '<div class="main-container" style="width:100%;">';
        $lang = $_GET['lang'] ?? 'de';
        // Sprachwahl als Block über der Karte
        echo '<div class="menuqr-language-switcher" style="width:100%;text-align:center;margin:20px 0 20px 0;display:block;">';
        echo '<form method="get" action="" style="display:inline-block;">';
        echo '<label for="lang" style="font-weight:bold;margin-right:10px;">Sprache:</label>';
        echo '<select name="lang" id="lang" onchange="this.form.submit()" style="padding:6px 12px;font-size:1rem;">';
        echo '<option value="de"' . ($lang === 'de' ? ' selected' : '') . '>Deutsch</option>';
        echo '<option value="en"' . ($lang === 'en' ? ' selected' : '') . '>English</option>';
        echo '</select>';
        echo '</form>';
        echo '</div>';
        // Karte darunter
        $pdfFile = $lang === 'en' ? '/public/speisekarte-en.pdf' : '/public/speisekarte.pdf';
        echo <<< HTML
        <div id="menuqr-pdf" class="menu-section2">
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
                <div class="page-wrapper">
                    <div id="canvasContainer" class="page-flip">
                        <canvas id="pdfCanvas1" class="canvas-page"></canvas>
                        <canvas id="pdfCanvas2" class="canvas-page" style="display: none;"></canvas>
                    </div>
                </div>
            </div>
            <div style="margin-top:10px;">
                <a href="$pdfFile" target="_blank" class="btn btn-primary" style="padding:8px 18px;font-size:1rem;">PDF herunterladen</a>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
        // PDF.js-Logik für zwei Seiten Desktop, eine Seite mobil
        const pdfUrl = '$pdfFile';
        console.log('PDF URL geladen:', pdfUrl);
        let pdfDoc = null, pageNum = 1, pageCount = 1;
        const canvas1 = document.getElementById('pdfCanvas1');
        const canvas2 = document.getElementById('pdfCanvas2');
        const pageNumSpan = document.getElementById('pageNum');
        const pageCountSpan = document.getElementById('pageCount');
        let isMobile = window.matchMedia('(max-width: 900px)').matches;

        function renderPages() {
            if (!pdfDoc) return;
            pageNumSpan.textContent = pageNum;
            pageCountSpan.textContent = pdfDoc.numPages;
            // Seite 1
            pdfDoc.getPage(pageNum).then(function(page) {
                const viewport = page.getViewport({ scale: 1.5 });
                canvas1.height = viewport.height;
                canvas1.width = viewport.width;
                page.render({ canvasContext: canvas1.getContext('2d'), viewport: viewport });
            });
            // Seite 2 (nur Desktop)
            if (!isMobile && pageNum < pdfDoc.numPages) {
                canvas2.style.display = 'block';
                pdfDoc.getPage(pageNum + 1).then(function(page) {
                    const viewport = page.getViewport({ scale: 1.5 });
                    canvas2.height = viewport.height;
                    canvas2.width = viewport.width;
                    page.render({ canvasContext: canvas2.getContext('2d'), viewport: viewport });
                });
            } else {
                canvas2.style.display = 'none';
            }
        }

        pdfjsLib.getDocument(pdfUrl).promise.then(function(doc) {
            pdfDoc = doc;
            pageCount = doc.numPages;
            console.log('PDF geladen:', pdfDoc);
            renderPages();
        }).catch(function(error) {
            console.error('Fehler beim Laden des PDFs:', error);
        });

        document.getElementById('nextPage').onclick = function() {
            if (isMobile) {
                if (pageNum < pageCount) pageNum++;
            } else {
                if (pageNum + 2 <= pageCount) pageNum += 2;
                else if (pageNum < pageCount) pageNum = pageCount;
            }
            console.log('Nächste Seite:', pageNum);
            renderPages();
        };
        document.getElementById('prevPage').onclick = function() {
            if (isMobile) {
                if (pageNum > 1) pageNum--;
            } else {
                if (pageNum > 2) pageNum -= 2;
                else pageNum = 1;
            }
            console.log('Vorherige Seite:', pageNum);
            renderPages();
        };
        document.getElementById('firstPage').onclick = function() {
            pageNum = 1;
            console.log('Erste Seite:', pageNum);
            renderPages();
        };
        document.getElementById('lastPage').onclick = function() {
            if (isMobile) {
                pageNum = pageCount;
            } else {
                pageNum = pageCount % 2 === 0 ? pageCount - 1 : pageCount;
            }
            console.log('Letzte Seite:', pageNum);
            renderPages();
        };
        window.addEventListener('resize', function() {
            isMobile = window.matchMedia('(max-width: 900px)').matches;
            console.log('Resize, isMobile:', isMobile);
            renderPages();
        });
        </script>
        HTML;
        echo '</div>';
    }
}

MenuQR::main();
 