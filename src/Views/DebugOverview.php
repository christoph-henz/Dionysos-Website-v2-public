<?php

namespace Dionysosv2\Views;

use PDO;

class DebugOverview extends Page
{
    public function __construct(PDO $database)
    {
        // Parent-Konstruktor zuerst aufrufen
        parent::__construct();
        
        // Database von Parent verwenden
        $this->_database = $database;
        $this->isLocal = $this->isDatabaseLocal();
    }

    private function isDatabaseLocal(): bool
    {
        try {
            $this->_database->getAttribute(PDO::ATTR_DRIVER_NAME);
            return $this->_database->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function generateMainBody(): void
    {
        // Prüfe ob Benutzer eingeloggt ist
        if (!$this->isUserLoggedIn()) {
            $this->redirectToLogin();
            return;
        }

        echo '<div class="debug-container">';
        echo '<div class="debug-header">';
        echo '<h1>🐛 Debug-Panel</h1>';
        echo '<a href="/admin" class="btn btn-secondary">← Zurück zur Admin-Übersicht</a>';
        echo '</div>';
        
        $this->generateTabs();
        $this->generateTelegramSection();
        $this->generateDebugTimeSection();
        
        echo '</div>';
        
        $this->generateStyles();
        $this->generateJavaScript();
    }

    private function isUserLoggedIn(): bool
    {
        session_start();
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    private function redirectToLogin(): void
    {
        header('Location: /login');
        exit;
    }

    private function generateTabs(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'telegram';
        
        echo '<div class="tab-navigation">';
        echo '<button class="tab-btn ' . ($currentTab === 'telegram' ? 'active' : '') . '" data-tab="telegram" onclick="showTab(\'telegram\', event)">🤖 Telegram</button>';
        echo '<button class="tab-btn ' . ($currentTab === 'debug-time' ? 'active' : '') . '" data-tab="debug-time" onclick="showTab(\'debug-time\', event)">⏰ Debug Zeit</button>';
        echo '</div>';
    }

    private function generateTelegramSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'telegram';
        
        echo '<div id="telegram" class="tab-content ' . ($currentTab === 'telegram' ? 'active' : '') . '">';
        echo '<h2>Telegram Bot Konfiguration</h2>';
        echo '<div class="telegram-config">';
        
        // Telegram Bot Status prüfen
        $this->displayTelegramStatus();
        
        echo '<div class="telegram-actions">';
        echo '<h3>📋 Verfügbare Aktionen</h3>';
        echo '<div class="action-grid">';
        
        echo '<div class="action-item">';
        echo '<h4>🔧 Bot konfigurieren</h4>';
        echo '<p>Telegram Bot Token und Chat-ID einrichten</p>';
        echo '<a href="/telegram/config" class="btn btn-primary">Bot konfigurieren</a>';
        echo '</div>';
        
        echo '<div class="action-item">';
        echo '<h4>🧪 Test-Nachricht</h4>';
        echo '<p>Sende eine Test-Nachricht um die Konfiguration zu prüfen</p>';
        echo '<button onclick="sendTestMessage()" class="btn btn-secondary">Test senden</button>';
        echo '</div>';
        
        echo '<div class="action-item">';
        echo '<h4>📊 Telegram Status</h4>';
        echo '<p>Aktuellen Status des Telegram Bots prüfen</p>';
        echo '<button onclick="checkTelegramStatus()" class="btn btn-info">Status prüfen</button>';
        echo '</div>';
        
        echo '<div class="action-item">';
        echo '<h4>📝 Webhook Info</h4>';
        echo '<p>Webhook-Informationen anzeigen und verwalten</p>';
        echo '<button onclick="getWebhookInfo()" class="btn btn-warning">Webhook Info</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        echo '<div class="telegram-logs">';
        echo '<h3>📜 Telegram Logs</h3>';
        echo '<div id="telegram-log-content" class="log-content">';
        echo '<p class="text-muted">Klicken Sie auf "Logs laden" um die neuesten Telegram-Aktivitäten zu sehen.</p>';
        echo '</div>';
        echo '<button onclick="loadTelegramLogs()" class="btn btn-outline">Logs laden</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    private function generateDebugTimeSection(): void
    {
        // Lese aktuellen Tab aus der URL
        $currentTab = $_GET['tab'] ?? 'telegram';
        
        echo '<div id="debug-time" class="tab-content ' . ($currentTab === 'debug-time' ? 'active' : '') . '">';
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
        
        echo '<div class="debug-impact">';
        echo '<h3>⚡ Auswirkungen der Debug-Zeit</h3>';
        echo '<div class="impact-grid">';
        echo '<div class="impact-item">';
        echo '<h4>📦 Bestellungen</h4>';
        echo '<p>Bestellungen werden mit der Debug-Zeit als "heute" behandelt</p>';
        echo '</div>';
        echo '<div class="impact-item">';
        echo '<h4>🍽️ Reservierungen</h4>';
        echo '<p>Reservierungen werden entsprechend der Debug-Zeit gefiltert</p>';
        echo '</div>';
        echo '<div class="impact-item">';
        echo '<h4>🕒 Öffnungszeiten</h4>';
        echo '<p>Restaurant-Status wird anhand der Debug-Zeit berechnet</p>';
        echo '</div>';
        echo '<div class="impact-item">';
        echo '<h4>📧 Benachrichtigungen</h4>';
        echo '<p>E-Mail und Telegram verwenden die Debug-Zeit für Zeitstempel</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    private function displayTelegramStatus(): void
    {
        echo '<div class="telegram-status-panel">';
        echo '<h3>📡 Bot Status</h3>';
        echo '<div id="telegram-status-content" class="status-content">';
        
        // Basis-Status anzeigen
        echo '<div class="status-item">';
        echo '<span class="status-label">Bot-Token:</span>';
        echo '<span class="status-value" id="bot-token-status">Wird geladen...</span>';
        echo '</div>';
        
        echo '<div class="status-item">';
        echo '<span class="status-label">Chat-ID:</span>';
        echo '<span class="status-value" id="chat-id-status">Wird geladen...</span>';
        echo '</div>';
        
        echo '<div class="status-item">';
        echo '<span class="status-label">Verbindung:</span>';
        echo '<span class="status-value" id="connection-status">Wird geprüft...</span>';
        echo '</div>';
        
        echo '<div class="status-item">';
        echo '<span class="status-label">Letzter Test:</span>';
        echo '<span class="status-value" id="last-test-status">Unbekannt</span>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    private function generateStyles(): void
    {
        echo <<<HTML
        <style>
            .debug-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 2rem;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }

            .debug-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #e0e0e0;
            }

            .debug-header h1 {
                margin: 0;
                color: #333;
                font-size: 2rem;
            }

            .tab-navigation {
                display: flex;
                gap: 0.5rem;
                margin-bottom: 2rem;
                border-bottom: 2px solid #e0e0e0;
                padding-bottom: 1rem;
            }

            .tab-btn {
                padding: 0.8rem 1.5rem;
                border: none;
                background: #f5f5f5;
                color: #666;
                cursor: pointer;
                border-radius: 8px 8px 0 0;
                font-weight: bold;
                transition: all 0.3s ease;
                border-bottom: 3px solid transparent;
            }

            .tab-btn:hover {
                background: #e8e8e8;
                color: #333;
            }

            .tab-btn.active {
                background: white;
                color: #ffab66;
                border-bottom: 3px solid #ffab66;
            }

            .tab-content {
                display: none;
                background: white;
                border-radius: 8px;
                padding: 2rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .tab-content.active {
                display: block;
            }

            .tab-content h2 {
                margin-top: 0;
                color: #333;
                font-size: 1.8rem;
                border-bottom: 2px solid #ffab66;
                padding-bottom: 0.5rem;
            }

            /* Telegram Styles */
            .telegram-config {
                display: grid;
                gap: 2rem;
            }

            .telegram-status-panel {
                background: #f8f9fa;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .telegram-status-panel h3 {
                margin-top: 0;
                color: #333;
            }

            .status-content {
                display: grid;
                gap: 1rem;
            }

            .status-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0;
                border-bottom: 1px solid #e0e0e0;
            }

            .status-item:last-child {
                border-bottom: none;
            }

            .status-label {
                font-weight: bold;
                color: #555;
            }

            .status-value {
                color: #333;
                font-family: monospace;
            }

            .action-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1.5rem;
                margin-top: 1rem;
            }

            .action-item {
                background: #f8f9fa;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 1.5rem;
                text-align: center;
                transition: all 0.3s ease;
            }

            .action-item:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }

            .action-item h4 {
                margin-top: 0;
                color: #333;
            }

            .action-item p {
                color: #666;
                margin-bottom: 1rem;
            }

            .telegram-logs {
                margin-top: 2rem;
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1.5rem;
            }

            .log-content {
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                padding: 1rem;
                min-height: 200px;
                max-height: 400px;
                overflow-y: auto;
                font-family: monospace;
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }

            /* Debug Time Styles */
            .debug-info {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .debug-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
                margin-top: 1rem;
            }

            .debug-item {
                background: white;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                padding: 1rem;
                text-align: center;
            }

            .debug-item.debug-active {
                border-color: #4caf50;
                background: #f8fff8;
            }

            .debug-item strong {
                display: block;
                margin-bottom: 0.5rem;
                color: #333;
            }

            .debug-item small {
                color: #666;
                font-size: 0.8rem;
            }

            .debug-controls {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .debug-form {
                display: grid;
                gap: 1rem;
            }

            .form-group {
                display: grid;
                gap: 0.5rem;
            }

            .form-group label {
                font-weight: bold;
                color: #333;
            }

            .form-group input {
                padding: 0.8rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 1rem;
            }

            .form-group input:focus {
                outline: none;
                border-color: #ffab66;
                box-shadow: 0 0 0 2px rgba(255, 171, 102, 0.2);
            }

            .form-group small {
                color: #666;
                font-size: 0.9rem;
            }

            .form-actions {
                display: flex;
                gap: 1rem;
                margin-top: 1rem;
            }

            .debug-examples {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 1.5rem;
                margin-bottom: 2rem;
            }

            .example-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                margin-top: 1rem;
            }

            .debug-impact {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 8px;
                padding: 1.5rem;
            }

            .impact-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
                margin-top: 1rem;
            }

            .impact-item {
                background: white;
                border-radius: 6px;
                padding: 1rem;
            }

            .impact-item h4 {
                margin-top: 0;
                color: #333;
            }

            .impact-item p {
                margin-bottom: 0;
                color: #666;
            }

            /* Button Styles */
            .btn {
                padding: 0.8rem 1.5rem;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: bold;
                text-decoration: none;
                display: inline-block;
                text-align: center;
                transition: all 0.3s ease;
                font-size: 0.9rem;
            }

            .btn-primary {
                background: #ffab66;
                color: white;
            }

            .btn-primary:hover {
                background: #ff9240;
            }

            .btn-secondary {
                background: #6c757d;
                color: white;
            }

            .btn-secondary:hover {
                background: #545b62;
            }

            .btn-info {
                background: #17a2b8;
                color: white;
            }

            .btn-info:hover {
                background: #138496;
            }

            .btn-warning {
                background: #ffc107;
                color: #212529;
            }

            .btn-warning:hover {
                background: #e0a800;
            }

            .btn-outline {
                background: transparent;
                color: #ffab66;
                border: 2px solid #ffab66;
            }

            .btn-outline:hover {
                background: #ffab66;
                color: white;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .debug-container {
                    padding: 1rem;
                }

                .debug-header {
                    flex-direction: column;
                    gap: 1rem;
                    text-align: center;
                }

                .tab-navigation {
                    flex-direction: column;
                }

                .action-grid {
                    grid-template-columns: 1fr;
                }

                .form-actions {
                    flex-direction: column;
                }

                .example-buttons {
                    flex-direction: column;
                }

                .impact-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        HTML;
    }

    private function generateJavaScript(): void
    {
        echo <<< 'HTML'
                <script>
                    function showTab(tabName, clickEvent) {
                        // Alle Tab-Inhalte ausblenden
                        const tabContents = document.querySelectorAll('.tab-content');
                        tabContents.forEach(content => content.classList.remove('active'));

                        // Alle Tab-Buttons deaktivieren
                        const tabButtons = document.querySelectorAll('.tab-btn');
                        tabButtons.forEach(btn => btn.classList.remove('active'));

                        // Gewählten Tab anzeigen
                        document.getElementById(tabName).classList.add('active');
                        
                        // Button aktiv markieren
                        if (clickEvent && clickEvent.target) {
                            clickEvent.target.classList.add('active');
                        } else {
                            document.querySelector('.tab-btn[data-tab="' + tabName + '"]').classList.add('active');
                        }
                        
                        // URL aktualisieren
                        const url = new URL(window.location);
                        url.searchParams.set('tab', tabName);
                        window.history.pushState({}, '', url);
                    }

                    // Debug Time Functions
                    function setDebugTime() {
                        const date = document.getElementById('debug_date').value;
                        const time = document.getElementById('debug_time').value;

                        fetch('/public/api/debug_time_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'set_debug_time',
                                date: date || null,
                                time: time || null
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Debug-Zeit erfolgreich gesetzt!');
                                refreshDebugInfo();
                            } else {
                                alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten');
                        });
                    }

                    function resetDebugTime() {
                        if (!confirm('Debug-Zeit zurücksetzen?')) return;

                        fetch('/public/api/debug_time_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'reset_debug_time'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Debug-Zeit zurückgesetzt!');
                                document.getElementById('debug_date').value = '';
                                document.getElementById('debug_time').value = '';
                                refreshDebugInfo();
                            } else {
                                alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten');
                        });
                    }

                    function setQuickTime(time) {
                        document.getElementById('debug_time').value = time;
                        setDebugTime();
                    }

                    function refreshDebugInfo() {
                        location.reload();
                    }

                    // Telegram Functions
                    function sendTestMessage() {
                        if (!confirm('Test-Nachricht an Telegram senden?')) return;

                        fetch('/public/api/telegram_status_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'send_test_message'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Test-Nachricht erfolgreich gesendet!');
                                updateLastTestStatus('Erfolgreich - ' + new Date().toLocaleString());
                            } else {
                                alert('Fehler beim Senden: ' + (data.error || 'Unbekannter Fehler'));
                                updateLastTestStatus('Fehlgeschlagen - ' + new Date().toLocaleString());
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten: ' + error.message);
                        });
                    }

                    function checkTelegramStatus() {
                        fetch('/public/api/telegram_status_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'check_status'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                updateTelegramStatus(data.status);
                                alert('Status erfolgreich aktualisiert!');
                            } else {
                                alert('Fehler beim Status-Check: ' + (data.error || 'Unbekannter Fehler'));
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten: ' + error.message);
                        });
                    }

                    function getWebhookInfo() {
                        fetch('/public/api/telegram_status_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'webhook_info'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Webhook Info:\n' + JSON.stringify(data.webhook_info, null, 2));
                            } else {
                                alert('Fehler beim Abrufen der Webhook-Info: ' + (data.error || 'Unbekannter Fehler'));
                            }
                        })
                        .catch(error => {
                            alert('Ein Fehler ist aufgetreten: ' + error.message);
                        });
                    }

                    function loadTelegramLogs() {
                        const logContent = document.getElementById('telegram-log-content');
                        logContent.innerHTML = '<p class="text-muted">Lade Logs...</p>';

                        fetch('/public/api/telegram_status_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'get_logs'
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                logContent.innerHTML = '<pre>' + (data.logs || 'Keine Logs verfügbar') + '</pre>';
                            } else {
                                logContent.innerHTML = '<p class="text-danger">Fehler beim Laden der Logs: ' + (data.error || 'Unbekannter Fehler') + '</p>';
                            }
                        })
                        .catch(error => {
                            logContent.innerHTML = '<p class="text-danger">Ein Fehler ist aufgetreten: ' + error.message + '</p>';
                        });
                    }

                    function updateTelegramStatus(status) {
                        if (status.bot_token) {
                            document.getElementById('bot-token-status').textContent = status.bot_token_configured ? '✅ Konfiguriert' : '❌ Nicht konfiguriert';
                        }
                        if (status.chat_id) {
                            document.getElementById('chat-id-status').textContent = status.chat_id_configured ? '✅ Konfiguriert' : '❌ Nicht konfiguriert';
                        }
                        if (status.connection) {
                            document.getElementById('connection-status').textContent = status.connection_ok ? '✅ Verbunden' : '❌ Nicht verbunden';
                        }
                    }

                    function updateLastTestStatus(status) {
                        document.getElementById('last-test-status').textContent = status;
                    }

                    // Initial Tab aus URL laden
                    document.addEventListener('DOMContentLoaded', function() {
                        const urlParams = new URLSearchParams(window.location.search);
                        const tabParam = urlParams.get('tab');
                        
                        if (tabParam && document.getElementById(tabParam)) {
                            showTab(tabParam);
                        }

                        // Initial Telegram Status laden
                        if (document.getElementById('telegram').classList.contains('active')) {
                            checkTelegramStatus();
                        }
                    });
                </script>
        HTML;
    }
}
