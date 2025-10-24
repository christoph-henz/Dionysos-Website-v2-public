<?php

namespace Dionysosv2\Views;
use Dionysosv2\Controller\MenuBuilder;
use Exception;

class OrderMenu extends Page
{
    /**
     * Properties
     */
    private \Dionysosv2\Controller\ArticleController $articleController;
    private $allArticles = [];
    private \Dionysosv2\Controller\CartController $cartController;
    private \Dionysosv2\Controller\OptionController $optionController;
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
        $this->articleController = new \Dionysosv2\Controller\ArticleController();
        $this->allArticles = $this->articleController->getAllArticles();
        $this->cartController = new \Dionysosv2\Controller\CartController();
        $this->optionController = new \Dionysosv2\Controller\OptionController();
        $this->loadSystemSettings();
    }

    /**
     * Lädt die Systemeinstellungen und prüft Öffnungszeiten
     */
    private function loadSystemSettings(): void
    {
        $stmt = $this->_database->prepare("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key IN ('order_system', 'pickup_system', 'delivery_system', 'order_system_enabled', 'pickup_system_enabled', 'delivery_system_enabled')
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        // Prüfe ob Restaurant geöffnet ist und Bestellungen angenommen werden
        $settingsModel = new \Dionysosv2\Models\Settings($this->_database);
        $isRestaurantOpen = $settingsModel->isOpen();
        $isOrderingAvailable = $settingsModel->isOrderingAvailable();
        
        $this->systemSettings = [
            'order_system' => ($settings['order_system'] ?? $settings['order_system_enabled'] ?? '1') === '1' && $isOrderingAvailable,
            'pickup_system' => ($settings['pickup_system'] ?? $settings['pickup_system_enabled'] ?? '1') === '1',
            'delivery_system' => ($settings['delivery_system'] ?? $settings['delivery_system_enabled'] ?? '1') === '1',
            'restaurant_open' => $isRestaurantOpen,
            'ordering_available' => $isOrderingAvailable
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
        // Generiere ein zufälliges Token und speichere es in der Sitzung
        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
        try {
            $page = new OrderMenu();
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
        // Prüfe ob das Bestellsystem verfügbar ist (inklusive Öffnungszeiten)
        if (!$this->systemSettings['order_system']) {
            $this->generatePageHeader('Bestellsystem nicht verfügbar');
            $this->generateOrderSystemClosedView();
            $this->generatePageFooter();
            return;
        }

        $this->generatePageHeader('Bestellung'); //to do: set optional parameters

        //$this->generateNav();
        $this->generateMainBody();
        $this->generatePageFooter();
    }

    /**
     * Generiert die Ansicht wenn das Bestellsystem nicht verfügbar ist
     */
    private function generateOrderSystemClosedView(): void
    {
        $this->generateIntroDisplay();
        
        echo '<div class="main-container">';
        echo '<div class="system-disabled-container">';
        echo '<div class="system-disabled-content">';
        
        $isRestaurantClosed = !$this->systemSettings['restaurant_open'];
        $isOrderingStopped = $this->systemSettings['restaurant_open'] && !$this->systemSettings['ordering_available'];
        
        if ($isOrderingStopped) {
            // Restaurant ist geöffnet, aber Bestellstopp (2 Stunden vor Schließung)
            echo '<div class="disabled-icon">⏰</div>';
            echo '<h1>Bestellstopp aktiv</h1>';
            echo '<p>Unser Online-Bestellsystem ist derzeit geschlossen, da wir 2 Stunden vor Schließung keine neuen Bestellungen mehr annehmen. Dies ermöglicht es unserer Küche, alle Bestellungen rechtzeitig zuzubereiten.</p>';
        } elseif ($isRestaurantClosed) {
            // Restaurant ist geschlossen
            echo '<div class="disabled-icon">🕐</div>';
            echo '<h1>Restaurant geschlossen</h1>';
            echo '<p>Unser Online-Bestellsystem ist derzeit nicht verfügbar, da wir außerhalb unserer Öffnungszeiten sind.</p>';
            
            echo '<div class="opening-hours-info">';
            echo '<h3>Unsere Öffnungszeiten:</h3>';
            echo '<div class="hours-display">';
            echo '<div class="hours-row"><strong>Dienstag - Samstag:</strong> 17:30 - 23:00 Uhr</div>';
            echo '<div class="hours-row"><strong>Sonntag:</strong> 11:30 - 22:00 Uhr</div>';
            echo '<div class="hours-row closed"><strong>Montag:</strong> Ruhetag</div>';
            echo '</div>';
            echo '<div class="ordering-note">';
            echo '<p><em>Hinweis: Bestellungen werden bis 2 Stunden vor Schließung angenommen.</em></p>';
            echo '</div>';
            echo '</div>';
        } else {
            // System ist manuell deaktiviert
            echo '<div class="disabled-icon">🛠️</div>';
            echo '<h1>Bestellsystem momentan nicht verfügbar</h1>';
            echo '<p>Unser Online-Bestellsystem ist derzeit deaktiviert. Wir bitten um Ihr Verständnis.</p>';
        }
        
        echo '<div class="alternative-options">';
        echo '<h3>Alternative Bestellmöglichkeiten:</h3>';
        echo '<div class="contact-option">';
        echo '<div class="contact-icon">📞</div>';
        echo '<div class="contact-info">';
        echo '<strong>Telefonisch bestellen</strong>';
        echo '<p>06021 25779</p>';
        
        if ($isOrderingStopped) {
            echo '<p>Noch bis zur Schließung verfügbar</p>';
        } else {
            echo '<p>Während unserer Öffnungszeiten</p>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '<div class="contact-option">';
        echo '<div class="contact-icon">🏪</div>';
        echo '<div class="contact-info">';
        echo '<strong>Direkt im Restaurant</strong>';
        echo '<p>Floßhafen 27, 63739 Aschaffenburg</p>';
        echo '<p>Spontane Bestellungen vor Ort möglich</p>';
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
        echo <<<'EOT'
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

            .opening-hours-info {
                margin: 2rem 0;
                padding: 1.5rem;
                background: #f8f9fa;
                border-radius: 8px;
            }

            .opening-hours-info h3 {
                color: #333;
                margin-bottom: 1rem;
            }

            .hours-display {
                text-align: left;
            }

            .hours-row {
                margin: 0.5rem 0;
                padding: 0.5rem;
            }

            .hours-row.closed {
                color: #999;
            }

            .ordering-note {
                margin-top: 1rem;
                padding: 1rem;
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 6px;
            }

            .ordering-note em {
                color: #856404;
                font-size: 0.9rem;
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
        EOT;
    }

    private function generateMainBody(){
        $this->generateIntroDisplay();
        $this->generateArticleData();
        $this->generateBody();
    }

    protected function additionalMetaData(): void
    {
        //Links for css or js
        echo <<< EOT
            <link rel="stylesheet" type="text/css" href="public/assets/css/home.css"/>
            <link rel="stylesheet" type="text/css" href="public/assets/css/order-menu.css"/>
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
        echo '</div></div>';
    }

    private function generateArticleData() {
        ob_start();
        ?>
        <div class="category-menu" id="categoryMenu">
            <?php
            $groupedByCategory = [];
            foreach ($this->allArticles as $article) {
                $catName = $article->getCategory()->getName();
                if (!isset($groupedByCategory[$catName])) {
                    $groupedByCategory[$catName] = [];
                }
                $groupedByCategory[$catName][] = $article;
            }

            foreach ($groupedByCategory as $category => $articles): ?>
                <a href="#cat-<?= htmlspecialchars($category) ?>"
                   class="category-link"
                   data-category="<?= htmlspecialchars($category) ?>">
                    <?= htmlspecialchars($category) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const links = document.querySelectorAll(".category-link");
                const sections = Array.from(links).map(link => {
                    const id = link.getAttribute("href").substring(1);
                    return document.getElementById(id);
                });

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            links.forEach(link => link.classList.remove("active"));
                            const activeLink = document.querySelector(`.category-link[href="#${entry.target.id}"]`);
                            if (activeLink) activeLink.classList.add("active");
                        }
                    });
                }, {
                    rootMargin: '-40% 0px -59% 0px',
                    threshold: 0.1
                });

                sections.forEach(section => {
                    if (section) observer.observe(section);
                });
            });
        </script>
        <div class="menu-section">
        <?php
        return ob_get_clean();
    }

    private function generateBody()
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $groupedByCategory = [];
        foreach ($this->allArticles as $article) {
            $catName = $article->getCategory()->getName();
            if (!isset($groupedByCategory[$catName])) {
                $groupedByCategory[$catName] = [];
            }
            $groupedByCategory[$catName][] = $article;
        }

        

        echo '<div class="main-container">';
        echo '<div class="menu-section">';
        foreach ($groupedByCategory as $category => $articles) {
            echo '<h2 id="cat-' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</h2>';
            foreach ($articles as $article) {
                // Hole die Optionsgruppen für diesen Artikel
                $optionGroups = $this->optionController->getOptionGroupsByArticleId($article->getId());
                $hasOptions = !empty($optionGroups);
                
                echo '<div class="article" data-article-id="' . $article->getId() . '">';
                echo '<div class="article-header">';
                echo '<div class="article-content">';
                echo '<strong>' . htmlspecialchars($article->getName()) . '</strong><br>';
                echo '<em>' . htmlspecialchars($article->getDescription()) . '</em>';
                echo '</div>';
                echo '<div class="article-actions">';
                
                if ($hasOptions) {
                    echo '<button class="options-button" onclick="toggleArticleOptions(' . $article->getId() . ')">Optionen</button>';
                    echo '<button class="plus-button" onclick="addToCartWithOptions(' . $article->getId() . ')">+</button>';
                } else {
                    echo '<button class="plus-button" onclick="addToCart(' . $article->getId() . ')">+</button>';
                }
                
                echo '<div class="price">' . number_format($article->getPrice(), 2) . ' €</div>';
                echo '</div>'; // article-actions
                echo '</div>'; // article-header
                
                // Optionen-Panel (ausgeblendet)
                if ($hasOptions) {
                    echo '<div class="article-options" id="options-' . $article->getId() . '" style="display: none;">';
                    
                    foreach ($optionGroups as $optionGroup) {
                        echo '<div class="option-group" data-group-id="' . $optionGroup->getId() . '">';
                        echo '<h4>' . htmlspecialchars($optionGroup->getName());
                        if ($optionGroup->isRequired()) {
                            echo ' <span class="required">*</span>';
                        }
                        echo '</h4>';
                        echo '<p class="option-description">' . htmlspecialchars($optionGroup->getDescription()) . '</p>';
                        
                        $inputType = $optionGroup->getMaxSelections() > 1 ? 'checkbox' : 'radio';
                        $inputName = 'option_group_' . $optionGroup->getId() . '_article_' . $article->getId();
                        
                        foreach ($optionGroup->getOptions() as $option) {
                            $optionId = $option->getId();
                            $inputId = $inputName . '_option_' . $optionId;
                            $priceModifier = $option->getPriceModifier();
                            $priceText = $priceModifier > 0 ? ' (+' . number_format($priceModifier, 2) . '€)' : 
                                        ($priceModifier < 0 ? ' (' . number_format($priceModifier, 2) . '€)' : '');
                            
                            echo '<div class="option-item">';
                            echo '<input type="' . $inputType . '" id="' . $inputId . '" name="' . $inputName . '" value="' . $optionId . '"';
                            if ($option->isDefault()) {
                                echo ' checked';
                            }
                            echo ' data-price="' . $priceModifier . '">';
                            echo '<label for="' . $inputId . '">';
                            echo htmlspecialchars($option->getName()) . $priceText;
                            if (!empty($option->getDescription())) {
                                echo '<br><small>' . htmlspecialchars($option->getDescription()) . '</small>';
                            }
                            echo '</label>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    
                    echo '<div class="option-actions">';
                    echo '<button class="add-with-options-btn" onclick="addArticleWithSelectedOptions(' . $article->getId() . ')">In den Warenkorb</button>';
                    echo '<div class="option-error" id="error-' . $article->getId() . '" style="display: none; color: red; margin-top: 10px;"></div>';
                    echo '</div>';
                    echo '</div>';
                }
                
                echo '</div>'; // article
            }
        }        echo <<<EOT
            <script>        
                function addToCart(articleId) { 
                    changeQuantity(articleId, 1);
                }
                
                function toggleArticleOptions(articleId) {
                    const optionsPanel = document.getElementById('options-' + articleId);
                    if (optionsPanel.style.display === 'none') {
                        optionsPanel.style.display = 'block';
                        optionsPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    } else {
                        optionsPanel.style.display = 'none';
                    }
                }
                
                function addToCartWithOptions(articleId) {
                    toggleArticleOptions(articleId);
                }
                
                function validateRequiredOptions(articleId) {
                    const article = document.querySelector('[data-article-id="' + articleId + '"]');
                    const optionGroups = article.querySelectorAll('.option-group');
                    const errors = [];
                    
                    optionGroups.forEach(group => {
                        const groupId = group.getAttribute('data-group-id');
                        const isRequired = group.querySelector('.required') !== null;
                        
                        if (isRequired) {
                            const checkedInputs = group.querySelectorAll('input:checked');
                            if (checkedInputs.length === 0) {
                                const groupName = group.querySelector('h4').textContent.replace(' *', '');
                                errors.push('Bitte wählen Sie eine Option für: ' + groupName);
                            }
                        }
                    });
                    
                    return errors;
                }
                
                function getSelectedOptions(articleId) {
                    const article = document.querySelector('[data-article-id="' + articleId + '"]');
                    const selectedOptions = [];
                    
                    article.querySelectorAll('.option-group input:checked').forEach(input => {
                        selectedOptions.push({
                            optionId: parseInt(input.value),
                            price: parseFloat(input.getAttribute('data-price') || 0)
                        });
                    });
                    
                    return selectedOptions;
                }
                
                function addArticleWithSelectedOptions(articleId) {
                    const errors = validateRequiredOptions(articleId);
                    const errorDiv = document.getElementById('error-' + articleId);
                    
                    if (errors.length > 0) {
                        errorDiv.innerHTML = errors.join('<br>');
                        errorDiv.style.display = 'block';
                        return;
                    }
                    
                    errorDiv.style.display = 'none';
                    
                    const selectedOptions = getSelectedOptions(articleId);
                    
                    // Sende Artikel mit Optionen an den Warenkorb
                    fetch('/api/cart_api.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            article_id: articleId, 
                            delta: 1,
                            options: selectedOptions
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        updateCart(data);
                        // Optionen-Panel schließen
                        document.getElementById('options-' + articleId).style.display = 'none';
                    })
                    .catch(error => {
                        errorDiv.innerHTML = 'Fehler beim Hinzufügen zum Warenkorb';
                        errorDiv.style.display = 'block';
                    });
                }
            
                function changeQuantity(articleId, delta) {
                    fetch('/api/cart_api.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            article_id: articleId, 
                            delta: delta 
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        updateCart(data);
                    })
                    .catch(error => {
                        // Fehler behandeln
                    });
                }
            </script>
            EOT;
        echo '</div>'; // menu-section
        echo $this->generateCartDisplay();
        echo '</div></div>'; // main-container
    }

    private function generateCartDisplay(): string
    {
        ob_start();
        ?>
        <!-- Floating Cart Button -->
        <button id="cart-fab" class="cart-fab">
            🛒 <span id="cart-count">0</span>
        </button>

        <!-- Offcanvas Cart -->
        <div id="cart-offcanvas" class="cart-offcanvas">
            <div class="cart-offcanvas-content">
                <button id="close-cart">&times;</button>
                <h3>Warenkorb</h3>
                <ul id="cart-list"></ul>
                <div class="cart-summary">
                    <div class="cart-total">
                        Gesamt: <span id="cart-total">0,00 €</span>
                    </div>
                    <button id="checkout-button" class="checkout-button" onclick="startCheckout()">
                        Weiter
                    </button>
                </div>
            </div>
        </div>

        <script>
            // Cart functionality
            (function() {
                // Global cart update function
                window.updateCart = function(cartData) {
                    const cartList = document.getElementById('cart-list');
                    if (!cartList) return;
                    
                    cartList.innerHTML = '';
                    
                    const items = Array.isArray(cartData.items) 
                        ? cartData.items 
                        : Object.values(cartData.items || {});
                    
                    // Update cart count and total first (before early return)
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);
                        cartCount.textContent = totalQuantity;
                    }

                    const cartTotal = document.getElementById('cart-total');
                    if (cartTotal) {
                        cartTotal.textContent = (cartData.total || 0).toFixed(2) + ' €';
                    }
                    
                    if (items.length === 0) {
                        cartList.innerHTML = '<li class="empty-cart">Ihr Warenkorb ist leer</li>';
                        return;
                    }
                    
                    items.forEach(item => {
                        const li = document.createElement('li');
                        
                        // Build options display
                        let optionsHtml = '';
                        if (item.options && item.options.length > 0) {
                            optionsHtml = '<div class="cart-item-options">';
                            item.options.forEach(option => {
                                const priceText = option.price > 0 ? ` (+${option.price.toFixed(2)}€)` : 
                                                 option.price < 0 ? ` (${option.price.toFixed(2)}€)` : '';
                                optionsHtml += `<span class="option-tag">${option.name}${priceText}</span>`;
                            });
                            optionsHtml += '</div>';
                        }
                        
                        li.innerHTML = `
                            <div class="cart-item">
                                <div class="cart-item-details">
                                    <div class="cart-item-name">${item.display_name || item.name}</div>
                                    ${optionsHtml}
                                </div>
                                <div class="item-controls">
                                    <button onclick="removeFromCart('${item.cart_key || item.id}')">-</button>
                                    <span class="quantity">${item.quantity}</span>
                                    <button onclick="addSameToCart('${item.cart_key || item.id}')">+</button>
                                    <span class="price">${item.total.toFixed(2)} €</span>
                                </div>
                            </div>
                        `;
                        cartList.appendChild(li);
                    });
                };

                // Add to cart function
                window.addToCart = function(articleId) {
                    changeQuantity(articleId, 1);
                };

                // Change quantity function
                window.changeQuantity = function(articleId, delta) {
                    fetch('public/api/cart_api.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            article_id: articleId, 
                            delta: delta 
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        updateCart(data);
                    })
                    .catch(error => {});
                };

                // Add same item to cart (for items with options)
                window.addSameToCart = function(cartKey) {
                    fetch('public/api/cart_api.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            cart_key: cartKey, 
                            delta: 1 
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        updateCart(data);
                    })
                    .catch(error => {});
                };

                // Remove from cart
                window.removeFromCart = function(cartKey) {
                    fetch('public/api/cart_api.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            cart_key: cartKey, 
                            delta: -1 
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP error! status: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        updateCart(data);
                    })
                    .catch(error => {});
                };

                // Cart toggle function
                window.toggleCart = function() {
                    const offcanvas = document.getElementById('cart-offcanvas');
                    offcanvas.classList.toggle('open');
                };

                function loadInitialCart() {
                    return fetch('public/api/cart_api.php', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            article_id: 0, 
                            delta: 0 
                        })
                    })
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok: ' + res.status);
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (!data || !data.items) {
                            return;
                        }
                        updateCart(data);
                    })
                    .catch(error => {
                    });
                }

                // Initialize when DOM is ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function() {
                        loadInitialCart();
                        document.getElementById('cart-fab').onclick = toggleCart;
                        document.getElementById('close-cart').onclick = toggleCart;
                    });
                } else {
                    loadInitialCart();
                    document.getElementById('cart-fab').onclick = toggleCart;
                    document.getElementById('close-cart').onclick = toggleCart;
                }
            })();
            function startCheckout() {
                // TODO: Implement checkout process
                window.location.href = '/order/submit';
            }
        </script>
        <?php
        return ob_get_clean();
    }
}

OrderMenu::main();