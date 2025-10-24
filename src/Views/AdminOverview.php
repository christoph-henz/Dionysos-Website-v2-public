<?php

namespace Dionysosv2\Views;

use Exception;

// Authentifizierung prüfen
require_once __DIR__ . '/../Controller/AuthController.php';
use Dionysosv2\Controller\AuthController;
$authController = new AuthController();
$authController->requireAuth();

class AdminOverview extends Page
{
    protected function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public static function main(): void
    {
        // UTF-8 Header setzen
        header('Content-Type: text/html; charset=UTF-8');
        
        try {
            $page = new AdminOverview();
            $page->processReceivedData();
            $page->generateView();
        } catch (Exception $e) {
            header("Content-type: text/html; charset=UTF-8");
            echo $e->getMessage();
        }
    }

    protected function processReceivedData(): void
    {
        parent::processReceivedData();
        // PDF-Generierung auf Button-Klick
        if (isset($_POST['regen_pdf_de'])) {
            $builder = new \Dionysosv2\Controller\MenuBuilder();
            $builder->generatePdf();
        }
        if (isset($_POST['regen_pdf_en'])) {
            $builder = new \Dionysosv2\Controller\MenuBuilder();
            $builder->generateEnglishPdf();
        }
    }

    protected function additionalMetaData(): void
    {
        // Zusätzliche Meta-Tags für Admin-Bereich
        echo '<meta name="robots" content="noindex, nofollow">';
        echo '<link rel="icon" type="image/x-icon" href="public/assets/img/favicon.ico">';
        echo '<link rel="stylesheet" href="public/assets/css/home.css">';
        echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>';
    }

    protected function generateView(): void
    {
        $this->generatePageHeader('Admin Übersicht');
        $this->generateAdminHeader();
        $this->generateMainBody();
        $this->generatePageFooter();
    }

    private function generateAdminHeader(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $username = $_SESSION['admin_username'] ?? 'Admin';
        
        echo '<div class="admin-header">';
        echo '<div class="admin-user-info">';
        echo '<span>👤 Angemeldet als: <strong>' . htmlspecialchars($username) . '</strong></span>';
        echo '<div class="admin-actions">';
        echo '<button id="auto-refresh-btn" onclick="toggleAutoRefresh()" class="btn btn-success" title="Auto-Refresh ist aktiv (alle 60 Sekunden)">⏸️ Auto-Refresh AN</button>';
        echo '<a href="/admin/change-password" class="btn btn-secondary">🔐 Passwort</a>';
        echo '<a href="/logout" class="btn btn-danger">🚪 Logout</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    protected function generateMainBody(): void
    {
        echo '<div class="admin-container">';
        echo '<h1 class="admin-title">🎛️ Restaurant Verwaltung</h1>';

        $this->generateTabs();
        $this->generateOrdersSection();
        $this->generateTodayReservationsSection();
        $this->generateAllReservationsSection();
        $this->generateSettingsSection();
        $this->generateGallerySection();
        $this->generateTelegramSection();
        $this->generateDebugSection();
        
        echo '</div>';
        
        $this->generateStyles();
        $this->generateJavaScript();
    }

    private function generateTabs(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        
        echo '<div class="tab-navigation">';
        echo '<button class="tab-btn ' . ($currentTab === 'orders' ? 'active' : '') . '" data-tab="orders" onclick="showTab(\'orders\', event)">📦 Bestellungen</button>';
        echo '<button class="tab-btn ' . ($currentTab === 'today-reservations' ? 'active' : '') . '" data-tab="today-reservations" onclick="showTab(\'today-reservations\', event)">🍽️ Heutige Reservierungen</button>';
        echo '<button class="tab-btn ' . ($currentTab === 'all-reservations' ? 'active' : '') . '" data-tab="all-reservations" onclick="showTab(\'all-reservations\', event)">📋 Alle Reservierungen</button>';
        echo '<button class="tab-btn ' . ($currentTab === 'settings' ? 'active' : '') . '" data-tab="settings" onclick="showTab(\'settings\', event)">⚙️ Einstellungen</button>';
        echo '<button class="tab-btn ' . ($currentTab === 'gallery' ? 'active' : '') . '" data-tab="gallery" onclick="showTab(\'gallery\', event)">🖼️ Galerie</button>';
        echo '<button class="tab-btn ' . ($currentTab === 'telegram' ? 'active' : '') . '" data-tab="telegram" onclick="showTab(\'telegram\', event)">🤖 Telegram</button>';
        echo '<button class="tab-btn ' . ($currentTab === 'debug' ? 'active' : '') . '" data-tab="debug" onclick="showTab(\'debug\', event)">🐛 Debug Zeit</button>';
        echo '</div>';
    }

    private function generateOrdersSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        
        echo '<div id="orders" class="tab-content ' . ($currentTab === 'orders' ? 'active' : '') . '">';
        echo '<h2>Bestellungen von heute</h2>';
        
        $orders = $this->getOrders();
        
        if (empty($orders)) {
            echo '<div class="no-data-extended">';
            echo '<p class="no-data">Keine Bestellungen von heute vorhanden.</p>';
            echo '</div>';
        } else {
            echo '<div class="orders-grid">';
            foreach ($orders as $order) {
                $this->renderOrderCard($order);
            }
            echo '</div>';
        }
        
        echo '</div>';
    }

    private function generateTodayReservationsSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        // Tag-Auswahl und Wochen-Navigation
        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        $weekStart = isset($_GET['week']) ? $_GET['week'] : date('Y-m-d', strtotime('monday this week', strtotime($selectedDate)));
        $weekStartTs = strtotime($weekStart);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = date('Y-m-d', strtotime("+{$i} day", $weekStartTs));
        }
        echo '<div id="today-reservations" class="tab-content ' . ($currentTab === 'today-reservations' ? 'active' : '') . '">';
        echo '<h2>Reservierungen für <span id="selected-date">' . date('d.m.Y', strtotime($selectedDate)) . '</span></h2>';
        // Wochenleiste
        echo '<div class="week-bar" style="display:flex;align-items:center;justify-content:center;margin-bottom:20px;gap:8px;">';
        $prevWeek = date('Y-m-d', strtotime('-7 days', $weekStartTs));
        $nextWeek = date('Y-m-d', strtotime('+7 days', $weekStartTs));
        echo '<a href="?tab=today-reservations&week=' . $prevWeek . '&date=' . $days[0] . '" class="week-arrow" style="font-size:1.5em;padding:0 10px;">&#8592;</a>';
        $currentMonth = date('m', strtotime(date('Y-m-d')));
        $weekMonth = date('m', $weekStartTs);
        $showMonth = ($weekMonth !== $currentMonth);
        foreach ($days as $day) {
            $active = ($day === $selectedDate) ? 'active-day' : '';
            $isToday = ($day === date('Y-m-d'));
            $label = $isToday ? 'heute' : date('d', strtotime($day));
            echo '<a href="?tab=today-reservations&week=' . $weekStart . '&date=' . $day . '" class="week-day ' . $active . '" style="padding:6px 12px;border-radius:6px;border:1px solid #ccc;margin:0 2px;' . ($active ? 'background:#6c757d;color:#fff;font-weight:bold;' : 'background:#f5f5f5;color:#333;') . '">' . $label . '</a>';
        }
        if ($showMonth) {
            echo '<span class="week-month" style="margin-left:12px;font-weight:bold;color:#6c757d;">' . date('F', $weekStartTs) . '</span>';
        }
        echo '<a href="?tab=today-reservations&week=' . $nextWeek . '&date=' . $days[6] . '" class="week-arrow" style="font-size:1.5em;padding:0 10px;">&#8594;</a>';
        echo '</div>';
        // Reservierungen für ausgewählten Tag
        $todayReservations = $this->getTodayReservations($selectedDate);
        if (empty($todayReservations)) {
            echo '<div class="no-data-extended">';
            echo '<p class="no-data">Keine Reservierungen für diesen Tag vorhanden.</p>';
            echo '</div>';
        } else {
            echo '<div class="reservations-grid">';
            foreach ($todayReservations as $reservation) {
                $this->renderTodayReservationCard($reservation);
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private function generateAllReservationsSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        $sortOptions = [
            'reservation_date' => 'Reservierdatum & Uhrzeit',
            'name' => 'Name',
            'status' => 'Status',
            'created_at' => 'Erstelldatum'
        ];
        $sort = $_GET['sort'] ?? 'reservation_date';
        $sortLabel = $sortOptions[$sort] ?? $sortOptions['reservation_date'];

        echo '<div id="all-reservations" class="tab-content ' . ($currentTab === 'all-reservations' ? 'active' : '') . '">';
        echo '<h2>Alle Reservierungen</h2>';
    // Zyklischer Sortier-Button
    $sortKeys = array_keys($sortOptions);
    $currentIndex = array_search($sort, $sortKeys);
    $nextIndex = ($currentIndex === false || $currentIndex === count($sortKeys) - 1) ? 0 : $currentIndex + 1;
    $nextSort = $sortKeys[$nextIndex];
    $nextLabel = $sortOptions[$nextSort];
    $nextUrl = '?tab=all-reservations&sort=' . $nextSort;
    echo '<div class="sort-dropdown-wrapper" style="margin-bottom:20px;">';
    echo '<label for="reservation-sort" style="font-weight:bold;">Sortieren nach:</label> ';
    echo '<button class="btn btn-secondary" id="reservation-sort-btn" style="margin-left:10px;" onclick="window.location.href=\'' . $nextUrl . '\'" title="Nächste Sortierung: ' . htmlspecialchars($nextLabel) . '">' . htmlspecialchars($sortLabel) . ' <span style="font-size:1.1em;">⟳</span></button>';
    echo '</div>';

        $allReservations = $this->getAllReservations($sort);
        if (empty($allReservations)) {
            echo '<p class="no-data">Keine Reservierungen vorhanden.</p>';
        } else {
            echo '<div class="reservations-grid">';
            foreach ($allReservations as $reservation) {
                $this->renderAllReservationCard($reservation);
            }
            echo '</div>';
        }
        echo '</div>';
    }

    // Alte Methode wurde durch generateTodayReservationsSection und generateAllReservationsSection ersetzt

    private function generateTelegramSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        
        echo '<div id="telegram" class="tab-content ' . ($currentTab === 'telegram' ? 'active' : '') . '">';
        echo '<h2>Telegram Bot Konfiguration</h2>';
        echo '<div class="telegram-config">';
        echo '<p>Konfigurieren Sie hier Ihren Telegram Bot für automatische Benachrichtigungen:</p>';
        echo '<a href="/telegram/config" class="btn btn-primary">🤖 Bot konfigurieren</a>';
        echo '<a href="/telegram/test" class="btn btn-secondary">🧪 Test-Nachricht senden</a>';
        echo '</div>';
        echo '</div>';
    }

    private function generateSettingsSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        echo '<div id="settings" class="tab-content ' . ($currentTab === 'settings' ? 'active' : '') . '">';
        echo '<h2>⚙️ System-Einstellungen</h2>';
        echo '<div class="settings-config">';
        // PDF-Buttons für Speisekarte
        echo '<div style="margin:20px 0;display:flex;gap:16px;">';
        echo '<form method="post" style="display:inline;">';
        echo '<button type="submit" name="regen_pdf_de" class="btn btn-primary">🇩🇪 PDF (Deutsch) neu generieren</button>';
        echo '</form>';
        echo '<form method="post" style="display:inline;">';
        echo '<button type="submit" name="regen_pdf_en" class="btn btn-primary">🇬🇧 PDF (Englisch) neu generieren</button>';
        echo '</form>';
            if (isset($_SESSION["admin_username"])) {
                echo '<a href="/admin/article-management" class="btn btn-info" style="margin-left:8px;">📝 Artikelverwaltung</a>';
            }
        echo '</div>';
        // System-Status laden
        $this->generateSystemSettings();
        
        // Einfache Bilder-Verwaltung (nur images-Tabelle)
        $this->generateImageManagement();
        
        echo '</div>';
        echo '</div>';
    }

    private function generateSystemSettings(): void
    {
        // Aktuelle Einstellungen laden
        $stmt = $this->_database->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('order_system', 'reservation_system', 'pickup_system', 'delivery_system')");
        $stmt->execute();
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        echo '<div class="system-settings-panel">';
        echo '<h3>🔧 System-Steuerung</h3>';
        echo '<div class="settings-grid">';
        
        $systemConfigs = [
            'order_system' => ['label' => 'Bestellsystem', 'icon' => '🛒', 'description' => 'Online-Bestellungen aktivieren/deaktivieren'],
            'reservation_system' => ['label' => 'Reservierungssystem', 'icon' => '📅', 'description' => 'Online-Reservierungen aktivieren/deaktivieren'],
            'pickup_system' => ['label' => 'Abholsystem', 'icon' => '🏃', 'description' => 'Abholung von Bestellungen aktivieren/deaktivieren'],
            'delivery_system' => ['label' => 'Liefersystem', 'icon' => '🚚', 'description' => 'Lieferung von Bestellungen aktivieren/deaktivieren']
        ];
        
        foreach ($systemConfigs as $key => $config) {
            $isEnabled = ($settings[$key] ?? '0') === '1';
            $statusClass = $isEnabled ? 'setting-enabled' : 'setting-disabled';
            $statusText = $isEnabled ? 'Aktiv' : 'Inaktiv';
            $statusIcon = $isEnabled ? '✅' : '❌';
            
            echo '<div class="setting-item ' . $statusClass . '">';
            echo '<div class="setting-header">';
            echo '<span class="setting-icon">' . $config['icon'] . '</span>';
            echo '<span class="setting-label">' . $config['label'] . '</span>';
            echo '<span class="setting-status">' . $statusIcon . ' ' . $statusText . '</span>';
            echo '</div>';
            echo '<div class="setting-description">' . $config['description'] . '</div>';
            echo '<div class="setting-actions">';
            echo '<button class="btn ' . ($isEnabled ? 'btn-danger' : 'btn-success') . '" onclick="toggleSystemSetting(\'' . $key . '\', ' . ($isEnabled ? 'false' : 'true') . ')">';
            echo $isEnabled ? 'Deaktivieren' : 'Aktivieren';
            echo '</button>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }

    private function generateImageManagement(): void
    {
        // Alle Bilder aus der Datenbank laden
        $stmt = $this->_database->prepare("
            SELECT i.id, i.name, i.created_at
            FROM images i 
            ORDER BY i.created_at DESC
        ");
        $stmt->execute();
        $images = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        echo '<div class="image-management-panel">';
        echo '<h3>🖼️ Bilder-Verwaltung</h3>';
        echo '<p class="panel-description">Hier können Sie Bilder hochladen und verwalten. Für die Galerie-Verwaltung verwenden Sie den separaten Galerie-Tab.</p>';
        
        // Upload-Bereich
        echo '<div class="image-upload-section">';
        echo '<h4>📤 Neues Bild hochladen</h4>';
        echo '<form id="imageUploadForm" enctype="multipart/form-data">';
        echo '<div class="upload-area">';
        echo '<input type="file" id="imageFile" name="image" accept="image/*" required>';
        echo '<label for="imageFile" class="upload-label">';
        echo '<span class="upload-icon">📁</span>';
        echo '<span>Bild auswählen (JPG, PNG, WEBP)</span>';
        echo '</label>';
        echo '</div>';
        echo '<button type="submit" class="btn btn-primary">🚀 Hochladen</button>';
        echo '</form>';
        echo '</div>';
        
        // Bilder-Liste (einfach, ohne Galerie-Features)
        echo '<div class="images-list-section">';
        echo '<h4>📋 Vorhandene Bilder (' . count($images) . ')</h4>';
        echo '<div class="images-grid-simple">';
        
        foreach ($images as $image) {
            echo '<div class="image-item-simple">';
            echo '<div class="image-preview-simple">';
            echo '<img src="/public/assets/img/' . htmlspecialchars($image['name']) . '" alt="' . htmlspecialchars($image['name']) . '">';
            echo '</div>';
            echo '<div class="image-info-simple">';
            echo '<div class="image-name">' . htmlspecialchars($image['name']) . '</div>';
            echo '<div class="image-meta">';
            echo '<small>Hochgeladen: ' . date('d.m.Y H:i', strtotime($image['created_at'])) . '</small>';
            echo '</div>';
            echo '</div>';
            echo '<div class="image-actions-simple">';
            echo '<button class="btn btn-danger btn-sm" onclick="deleteImage(' . $image['id'] . ', \'' . htmlspecialchars($image['name']) . '\')">🗑️ Löschen</button>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    private function generateGallerySection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        
        echo '<div id="gallery" class="tab-content ' . ($currentTab === 'gallery' ? 'active' : '') . '">';
        echo '<h2>🖼️ Galerie-Verwaltung</h2>';
        echo '<div class="gallery-config">';
        
        // Galerie-Bilder und verfügbare Bilder laden
        $this->generateGalleryManagement();
        
        echo '</div>';
        echo '</div>';
    }

    private function generateGalleryManagement(): void
    {
        // Aktuelle Galerie-Bilder laden
        $stmt = $this->_database->prepare("
            SELECT g.id as gallery_id, g.display_order, g.description, g.active,
                   i.id as image_id, i.name as image_name, i.created_at
            FROM gallery g
            JOIN images i ON g.image_id = i.id
            WHERE g.active = 1
            ORDER BY g.display_order ASC
        ");
        $stmt->execute();
        $galleryImages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Verfügbare Bilder (nicht in Galerie) laden
        $stmt = $this->_database->prepare("
            SELECT i.id, i.name, i.created_at
            FROM images i
            WHERE i.id NOT IN (
                SELECT image_id FROM gallery WHERE active = 1
            )
            ORDER BY i.created_at DESC
        ");
        $stmt->execute();
        $availableImages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        echo '<div class="gallery-management">';
        
        // Aktuelle Galerie
        echo '<div class="gallery-current-section">';
        echo '<h3>🎨 Aktuelle Galerie (' . count($galleryImages) . ' Bilder)</h3>';
        
        if (empty($galleryImages)) {
            echo '<div class="no-gallery-items">';
            echo '<p>Die Galerie ist leer. Fügen Sie Bilder aus der Liste unten hinzu.</p>';
            echo '</div>';
        } else {
            echo '<div class="gallery-items-list">';
            echo '<div class="gallery-controls-info">';
            echo '<p><i>💡 Ziehen Sie die Bilder per Drag & Drop, um die Reihenfolge zu ändern</i></p>';
            echo '</div>';
            
            echo '<div id="galleryItemsList" class="gallery-sortable">';
            foreach ($galleryImages as $item) {
                $this->renderGalleryItem($item);
            }
            echo '</div>';
        }
        echo '</div>';
        
        // Verfügbare Bilder
        echo '<div class="gallery-available-section">';
        echo '<h3>➕ Verfügbare Bilder (' . count($availableImages) . ' Bilder)</h3>';
        
        if (empty($availableImages)) {
            echo '<div class="no-available-images">';
            echo '<p>Keine verfügbaren Bilder. Laden Sie zuerst Bilder in den Einstellungen hoch.</p>';
            echo '</div>';
        } else {
            echo '<div class="available-images-grid">';
            foreach ($availableImages as $image) {
                $this->renderAvailableImage($image);
            }
            echo '</div>';
        }
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    private function renderGalleryItem(array $item): void
    {
        echo '<div class="gallery-item" data-gallery-id="' . $item['gallery_id'] . '" data-order="' . $item['display_order'] . '">';
        echo '<div class="gallery-item-handle">⋮⋮</div>';
        echo '<div class="gallery-item-image">';
        echo '<img src="/public/assets/img/' . htmlspecialchars($item['image_name']) . '" alt="' . htmlspecialchars($item['image_name']) . '">';
        echo '</div>';
        echo '<div class="gallery-item-info">';
        echo '<div class="gallery-item-name">' . htmlspecialchars($item['image_name']) . '</div>';
        echo '<div class="gallery-item-order">Reihenfolge: ' . $item['display_order'] . '</div>';
        echo '<div class="gallery-item-description">';
        echo '<label>Beschreibung:</label>';
        echo '<textarea class="description-input" data-gallery-id="' . $item['gallery_id'] . '" placeholder="Beschreibung eingeben...">' . htmlspecialchars($item['description'] ?? '') . '</textarea>';
        echo '</div>';
        echo '</div>';
        echo '<div class="gallery-item-actions">';
        echo '<button class="btn btn-success btn-sm" onclick="saveGalleryDescription(' . $item['gallery_id'] . ')">💾 Speichern</button>';
        echo '<button class="btn btn-warning btn-sm" onclick="removeFromGallery(' . $item['gallery_id'] . ')">🚫 Entfernen</button>';
        echo '</div>';
        echo '</div>';
    }

    private function renderAvailableImage(array $image): void
    {
        echo '<div class="available-image-item">';
        echo '<div class="available-image-preview">';
        echo '<img src="/public/assets/img/' . htmlspecialchars($image['name']) . '" alt="' . htmlspecialchars($image['name']) . '">';
        echo '</div>';
        echo '<div class="available-image-info">';
        echo '<div class="available-image-name">' . htmlspecialchars($image['name']) . '</div>';
        echo '<div class="available-image-meta">';
        echo '<small>Hochgeladen: ' . date('d.m.Y H:i', strtotime($image['created_at'])) . '</small>';
        echo '</div>';
        echo '</div>';
        echo '<div class="available-image-actions">';
        echo '<button class="btn btn-success btn-sm" onclick="addToGallery(' . $image['id'] . ')">➕ Hinzufügen</button>';
        echo '</div>';
        echo '</div>';
    }

    private function generateDebugSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'orders';
        
        echo '<div id="debug" class="tab-content ' . ($currentTab === 'debug' ? 'active' : '') . '">';
        echo '<h2>Debug Zeit-Simulation</h2>';
        echo '<div class="debug-config">';
        
        // Settings-Instanz für Debug-Info erstellen
        $settings = new \Dionysosv2\Models\Settings($this->_database);
        $debugInfo = $settings->getDebugInfo();
        
        echo '<div class="debug-info">';
        echo '<h3>📊 Aktuelle Zeit-Informationen</h3>';
        echo '<div class="debug-grid">';
        
        if ($debugInfo['debug_mode']) {
            echo '<div class="debug-item debug-active">';
            echo '<strong>🟢 Debug-Modus aktiv</strong>';
            echo '</div>';
        } else {
            echo '<div class="debug-item">';
            echo '<strong>🔴 Debug-Modus inaktiv</strong>';
            echo '</div>';
        }
        
        echo '<div class="debug-item">';
        echo '<strong>Aktuelles Datum:</strong> ' . htmlspecialchars($debugInfo['current_date']);
        echo '<br><small>Real: ' . htmlspecialchars($debugInfo['real_date']) . '</small>';
        echo '</div>';
        
        echo '<div class="debug-item">';
        echo '<strong>Aktuelle Zeit:</strong> ' . htmlspecialchars($debugInfo['current_time']);
        echo '<br><small>Real: ' . htmlspecialchars($debugInfo['real_time']) . '</small>';
        echo '</div>';
        
        echo '<div class="debug-item">';
        echo '<strong>Wochentag:</strong> ' . htmlspecialchars($debugInfo['current_day']);
        echo '<br><small>Real: ' . htmlspecialchars($debugInfo['real_day']) . '</small>';
        echo '</div>';
        
        echo '</div></div>';
        
        echo '<div class="debug-controls">';
        echo '<h3>⏰ Zeit-Simulation</h3>';
        echo '<form id="debugForm" class="debug-form">';
        
        echo '<div class="form-group">';
        echo '<label for="debug_date">📅 Debug-Datum:</label>';
        echo '<input type="date" id="debug_date" name="debug_date" value="' . ($debugInfo['debug_date'] ?? '') . '">';
        echo '<small>Leer lassen für aktuelles Datum</small>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="debug_time">🕐 Debug-Zeit:</label>';
        echo '<input type="time" id="debug_time" name="debug_time" value="' . ($debugInfo['debug_time'] ?? '') . '">';
        echo '<small>Leer lassen für aktuelle Zeit</small>';
        echo '</div>';
        
        echo '<div class="form-actions">';
        echo '<button type="button" onclick="setDebugTime()" class="btn btn-primary">💾 Zeit setzen</button>';
        echo '<button type="button" onclick="resetDebugTime()" class="btn btn-secondary">🔄 Zurücksetzen</button>';
        echo '<button type="button" onclick="refreshDebugInfo()" class="btn btn-info">🔄 Aktualisieren</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        
        echo '<div class="debug-examples">';
        echo '<h3>💡 Schnell-Aktionen</h3>';
        echo '<div class="example-buttons">';
        echo '<button onclick="setQuickTime(\'08:00\')" class="btn btn-outline">🌅 Morgens (8:00)</button>';
        echo '<button onclick="setQuickTime(\'12:00\')" class="btn btn-outline">🌞 Mittag (12:00)</button>';
        echo '<button onclick="setQuickTime(\'18:00\')" class="btn btn-outline">🌆 Abend (18:00)</button>';
        echo '<button onclick="setQuickTime(\'22:00\')" class="btn btn-outline">🌙 Spät (22:00)</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    private function getOrders(): array
    {
        try {
            // Fallback für Umgebungserkennung falls $this->isLocal nicht initialisiert ist
            $isLocalEnv = isset($this->isLocal) ? $this->isLocal : (file_exists(__DIR__ . '/../../database.db'));
            
            // Test: Prüfe ob überhaupt Bestellungen in der Datenbank existieren
            $testStmt = $this->_database->prepare("SELECT COUNT(*) as total FROM invoice");
            $testStmt->execute();
            $totalOrders = $testStmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($isLocalEnv) {
                $this->_database->exec("PRAGMA encoding = 'UTF-8'");
                // Nur Bestellungen vom aktuellen Tag anzeigen
                $stmt = $this->_database->prepare("
                    SELECT i.*, 
                           GROUP_CONCAT(
                               oi.quantity || '|' || a.name || '|' || 
                               COALESCE(oi.options_json, '[]') || '|' || 
                               oi.total_price, 
                               '||'
                           ) as items
                    FROM invoice i
                    LEFT JOIN order_item oi ON i.id = oi.invoice_id
                    LEFT JOIN article a ON oi.article_id = a.id
                    WHERE date(i.created_on) = date('now')
                    GROUP BY i.id
                    ORDER BY i.created_on DESC
                ");
                $stmt->execute();
                $orders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                // MySQL Syntax
                $stmt = $this->_database->prepare("
                    SELECT i.*, 
                           GROUP_CONCAT(
                               CONCAT(oi.quantity, '|', a.name, '|', 
                                      COALESCE(oi.options_json, '[]'), '|', 
                                      oi.total_price) 
                               SEPARATOR '||'
                           ) as items
                    FROM invoice i
                    LEFT JOIN order_item oi ON i.id = oi.invoice_id
                    LEFT JOIN article a ON oi.article_id = a.id
                    WHERE DATE(i.created_on) = CURDATE()
                    GROUP BY i.id
                    ORDER BY i.created_on DESC
                ");
                $stmt->execute();
                $orders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            
            return $orders;
        } catch (Exception $e) {
            error_log("Fehler beim Laden der Bestellungen: " . $e->getMessage());
            return [];
        }
    }

    private function getReservations(): array
    {
        // Diese Methode bleibt als Fallback für andere Teile der App
        return $this->getAllReservations();
    }

    private function getTodayReservations($date = null): array
    {
        try {
            $isLocalEnv = isset($this->isLocal) ? $this->isLocal : (file_exists(__DIR__ . '/../../database.db'));
            $date = $date ?? date('Y-m-d');
            if ($isLocalEnv) {
                $stmt = $this->_database->prepare("
                    SELECT * FROM reservations 
                    WHERE reservation_date = :date
                    ORDER BY reservation_time ASC
                ");
                $stmt->bindValue(':date', $date);
                $stmt->execute();
                $reservations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->_database->prepare("
                    SELECT * FROM reservations 
                    WHERE reservation_date = :date
                    ORDER BY reservation_time ASC
                ");
                $stmt->bindValue(':date', $date);
                $stmt->execute();
                $reservations = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
            return $reservations;
        } catch (Exception $e) {
            error_log("Fehler beim Laden der Reservierungen für den Tag: " . $e->getMessage());
            return [];
        }
    }

    private function getAllReservations($sort = 'reservation_date'): array
    {
        try {
            // Zeige alle zukünftigen Reservierungen und die der letzten 7 Tage
            $isLocalEnv = isset($this->isLocal) ? $this->isLocal : (file_exists(__DIR__ . '/../../database.db'));
            $orderBy = '';
            switch ($sort) {
                case 'name':
                    $orderBy = 'LOWER(first_name), LOWER(last_name)';
                    break;
                case 'status':
                    $orderBy = 'status';
                    break;
                case 'created_at':
                    $orderBy = 'created_at DESC';
                    break;
                case 'reservation_date':
                default:
                    $orderBy = 'reservation_date DESC, reservation_time ASC';
                    break;
            }
            if ($isLocalEnv) {
                $stmt = $this->_database->prepare("
                    SELECT * FROM reservations 
                    WHERE reservation_date >= date('now', '-6 days')
                       OR reservation_date >= date('now')
                    ORDER BY $orderBy
                ");
            } else {
                $stmt = $this->_database->prepare("
                    SELECT * FROM reservations 
                    WHERE reservation_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                       OR reservation_date >= CURDATE()
                    ORDER BY $orderBy
                ");
            }
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Fehler beim Laden aller Reservierungen: " . $e->getMessage());
            return [];
        }
    }

    private function renderOrderCard(array $order): void
    {
        $status = $order['status'] ?? 'pending';
        $statusClass = $this->getStatusClass($status);
        $statusText = $this->getStatusText($status);
        $isDelivery = !empty($order['street']);
        
        $isOverdue = false;
        if ($status === 'accepted') {
            $orderTime = strtotime($order['created_on']);
            $twoHoursAgo = time() - (2 * 60 * 60);
            $isOverdue = $orderTime < $twoHoursAgo;
        }
        
        $cardClass = $statusClass . ($isOverdue ? ' status-overdue' : '');
        
        echo '<div class="order-card ' . $cardClass . '">';
        echo '<div class="order-header">';
        echo '<h3>Bestellung #' . $order['id'] . '</h3>';
        echo '<span class="status-badge ' . $cardClass . '">' . $statusText;
        if ($isOverdue) {
            echo ' ⚠️';
        }
        echo '</span>';
        echo '</div>';
        
        echo '<div class="order-details">';
        echo '<p><strong>Kunde:</strong> ' . htmlspecialchars($order['name'], ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><strong>E-Mail:</strong> ' . htmlspecialchars($order['email'], ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><strong>Telefon:</strong> ' . htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><strong>Bezahlmethode:</strong> ' . htmlspecialchars($order['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>';
        
        if ($isDelivery) {
            echo '<p><strong>Lieferung an:</strong><br>';
            echo htmlspecialchars($order['street'] . ' ' . $order['number'], ENT_QUOTES, 'UTF-8') . '<br>';
            echo htmlspecialchars($order['postal_code'] . ' ' . $order['city'], ENT_QUOTES, 'UTF-8') . '</p>';
        } else {
            echo '<p><strong>🏃 Abholung</strong></p>';
        }
        
        echo '<p><strong>Artikel:</strong></p>';
        $this->renderOrderItems($order['items'] ?? '');
        echo '<p><strong>Gesamtsumme:</strong> ' . number_format($order['total_amount'], 2) . '€</p>';
        echo '<p><strong>Bestellt am:</strong> ' . date('d.m.Y H:i', strtotime($order['created_on'])) . '</p>';
        
        if ($isOverdue) {
            echo '<p class="overdue-warning"><strong>⚠️ Warnung:</strong> Bestellung ist über 2 Stunden alt!</p>';
        }
        echo '</div>';
        
        // Status-spezifische Aktionen
        if ($status === 'pending') {
            echo '<div class="order-actions">';
            echo '<button onclick="updateOrderStatus(' . $order['id'] . ', \'accepted\')" class="btn btn-success">✅ Annehmen</button>';
            echo '<button onclick="updateOrderStatus(' . $order['id'] . ', \'cancelled\')" class="btn btn-danger">❌ Stornieren</button>';
            echo '</div>';
        } elseif ($status === 'accepted') {
            echo '<div class="order-actions">';
            echo '<button onclick="updateOrderStatus(' . $order['id'] . ', \'finished\')" class="btn btn-primary">🏁 Fertigstellen</button>';
            echo '<button onclick="updateOrderStatus(' . $order['id'] . ', \'cancelled\')" class="btn btn-danger">❌ Stornieren</button>';
            echo '</div>';
        }
        
        echo '</div>';
    }

    // Alte Methode wurde durch renderTodayReservationCard und renderAllReservationCard ersetzt

    private function renderTodayReservationCard(array $reservation): void
    {
        $statusClass = $this->getReservationStatusClass($reservation['status'] ?? 'pending');
        $statusText = $this->getReservationStatusText($reservation['status'] ?? 'pending');
        
        echo '<div class="reservation-card ' . $statusClass . '">';
        echo '<div class="reservation-header">';
        echo '<h3>Reservierung #' . $reservation['id'] . '</h3>';
        echo '<span class="status-badge ' . $statusClass . '">' . $statusText . '</span>';
        echo '</div>';
        
        echo '<div class="reservation-details">';
        echo '<p><strong>Gast:</strong> ' . htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) . '</p>';
        echo '<p><strong>E-Mail:</strong> ' . htmlspecialchars($reservation['email']) . '</p>';
        echo '<p><strong>Telefon:</strong> ' . htmlspecialchars($reservation['phone']) . '</p>';
        echo '<p><strong>Zeit:</strong> ' . $reservation['reservation_time'] . '</p>';
        echo '<p><strong>Gäste:</strong> ' . $reservation['guests'] . '</p>';
        
        if (!empty($reservation['notes'])) {
            echo '<p><strong>Anmerkungen:</strong><br>' . htmlspecialchars($reservation['notes']) . '</p>';
        }
        
        echo '<p><strong>Reserviert am:</strong> ' . date('d.m.Y H:i', strtotime($reservation['created_at'])) . '</p>';
        echo '</div>';
        
        // Alle Aktionen für heutige Reservierungen
        $currentStatus = $reservation['status'] ?? 'pending';
        echo '<div class="reservation-actions">';
        
        if ($currentStatus === 'pending') {
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'confirmed\')" class="btn btn-success">✅ Annehmen</button>';
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'rejected\')" class="btn btn-danger">❌ Stornieren</button>';
        } elseif ($currentStatus === 'confirmed') {
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'arrived\')" class="btn btn-primary">🟢 Angekommen</button>';
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'no_show\')" class="btn btn-warning">🔴 No Show</button>';
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'rejected\')" class="btn btn-danger">❌ Stornieren</button>';
        } elseif ($currentStatus === 'arrived') {
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'confirmed\')" class="btn btn-secondary">🔄 Zurück zu Bestätigt</button>';
        } elseif ($currentStatus === 'no_show') {
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'confirmed\')" class="btn btn-secondary">🔄 Zurück zu Bestätigt</button>';
        } elseif ($currentStatus === 'rejected') {
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'confirmed\')" class="btn btn-secondary">🔄 Reaktivieren</button>';
        }
        
        echo '</div>';
        echo '</div>';
    }

    private function renderAllReservationCard(array $reservation): void
    {
        $statusClass = $this->getReservationStatusClass($reservation['status'] ?? 'pending');
        $statusText = $this->getReservationStatusText($reservation['status'] ?? 'pending');
        
        echo '<div class="reservation-card ' . $statusClass . '">';
        echo '<div class="reservation-header">';
        echo '<h3>Reservierung #' . $reservation['id'] . '</h3>';
        echo '<span class="status-badge ' . $statusClass . '">' . $statusText . '</span>';
        echo '</div>';
        
        echo '<div class="reservation-details">';
        echo '<p><strong>Gast:</strong> ' . htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']) . '</p>';
        echo '<p><strong>E-Mail:</strong> ' . htmlspecialchars($reservation['email']) . '</p>';
        echo '<p><strong>Telefon:</strong> ' . htmlspecialchars($reservation['phone']) . '</p>';
        echo '<p><strong>Datum:</strong> ' . date('d.m.Y', strtotime($reservation['reservation_date'])) . '</p>';
        echo '<p><strong>Zeit:</strong> ' . $reservation['reservation_time'] . '</p>';
        echo '<p><strong>Gäste:</strong> ' . $reservation['guests'] . '</p>';
        
        if (!empty($reservation['notes'])) {
            echo '<p><strong>Anmerkungen:</strong><br>' . htmlspecialchars($reservation['notes']) . '</p>';
        }
        
        echo '<p><strong>Reserviert am:</strong> ' . date('d.m.Y H:i', strtotime($reservation['created_at'])) . '</p>';
        echo '</div>';
        
        // Nur Basis-Aktionen für alle Reservierungen
        $currentStatus = $reservation['status'] ?? 'pending';
        echo '<div class="reservation-actions">';
        
        if ($currentStatus === 'pending') {
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'confirmed\')" class="btn btn-success">✅ Annehmen</button>';
            echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'rejected\')" class="btn btn-danger">❌ Ablehnen</button>';
        }
            // Stornieren für bestätigte Reservierungen
            if ($currentStatus === 'confirmed') {
                echo '<button onclick="updateReservationStatus(' . $reservation['id'] . ', \'rejected\')" class="btn btn-danger">❌ Stornieren</button>';
            }
        
        echo '</div>';
        echo '</div>';
    }

    private function getStatusClass(string $status): string
    {
        switch ($status) {
            case 'accepted': return 'status-accepted';
            case 'finished': return 'status-finished';
            case 'cancelled': return 'status-cancelled';
            case 'pending':
            default: return 'status-pending';
        }
    }

    private function getStatusText(string $status): string
    {
        switch ($status) {
            case 'accepted': return '🔄 Angenommen';
            case 'finished': return '✅ Fertig';
            case 'cancelled': return '❌ Storniert';
            case 'pending':
            default: return '⏳ Wartet';
        }
    }

    private function getReservationStatusClass(string $status): string
    {
        switch ($status) {
            case 'confirmed': return 'status-confirmed';
            case 'rejected': return 'status-rejected';
            case 'arrived': return 'status-arrived';
            case 'no_show': return 'status-no-show';
            case 'pending':
            default: return 'status-pending';
        }
    }

    private function getReservationStatusText(string $status): string
    {
        switch ($status) {
            case 'confirmed': return '✅ Bestätigt';
            case 'rejected': return '❌ Abgelehnt';
            case 'arrived': return '🟢 Angekommen';
            case 'no_show': return '🔴 No Show';
            case 'pending':
            default: return '⏳ Wartend';
        }
    }

    private function generateStyles(): void
    {
        echo <<<HTML
        <link rel="stylesheet" href="/public/assets/css/admin.css">
        HTML;
    }

    private function generateJavaScript(): void
    {
        echo <<< 'HTML'
                <script src="/public/assets/js/admin.js"></script>
        HTML;
    }

    private function renderOrderItems($itemsString) {
        if (empty($itemsString)) {
            echo '<p class="no-items">Keine Artikel</p>';
            return;
        }

        // Neues Format: quantity|articleName|optionsJson|price
        $items = explode('||', $itemsString);
        echo '<ul class="order-items-list">';
        
        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) continue;
            
            // Parse format: "quantity|articleName|optionsJson|price"
            $parts = explode('|', $item);
            if (count($parts) >= 4) {
                $quantity = trim($parts[0]);
                $articleName = trim($parts[1]);
                $optionsJson = trim($parts[2]);
                $price = trim($parts[3]);
                
                echo '<li class="order-item">';
                echo '<div class="item-main">';
                echo '<span class="quantity">' . htmlspecialchars($quantity, ENT_QUOTES, 'UTF-8') . 'x</span>';
                echo '<span class="article-name">' . htmlspecialchars($articleName, ENT_QUOTES, 'UTF-8') . '</span>';
                echo '<span class="item-price">' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '€</span>';
                echo '</div>';
                
                // Parse JSON options wenn vorhanden
                if (!empty($optionsJson) && $optionsJson !== '[]') {
                    $optionsArray = json_decode($optionsJson, true, 512, JSON_UNESCAPED_UNICODE);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Fallback: Versuche Unicode-Escape-Sequenzen zu dekodieren
                        $decodedJson = json_decode('"' . str_replace('"', '\\"', $optionsJson) . '"');
                        if ($decodedJson) {
                            $optionsArray = json_decode($decodedJson, true, 512, JSON_UNESCAPED_UNICODE);
                        }
                    }
                    
                    if (is_array($optionsArray) && !empty($optionsArray)) {
                        $optionTexts = [];
                        foreach ($optionsArray as $option) {
                            if (isset($option['name'])) {
                                $optionText = $option['name'];
                                if (isset($option['price']) && $option['price'] > 0) {
                                    $optionText .= ' (+' . number_format($option['price'], 2) . '€)';
                                }
                                $optionTexts[] = $optionText;
                            }
                        }
                        if (!empty($optionTexts)) {
                            echo '<div class="item-options">Optionen: ' . htmlspecialchars(implode(', ', $optionTexts), ENT_QUOTES, 'UTF-8') . '</div>';
                        }
                    }
                }
                echo '</li>';
            } else {
                // Fallback für unbekanntes Format
                echo '<li class="order-item-fallback">' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }
        
        echo '</ul>';
    }
}

AdminOverview::main();
