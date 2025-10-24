<?php
/**
 * Debug-Tool für Dionysos-Website-v2
 * Diese Datei hilft bei der Diagnose von Routing-, Datenbank- und Fehlerprobleme
 */

// Fehlerberichterstattung maximieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session starten, falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Header für HTML-Ausgabe
header("Content-type: text/html; charset=UTF-8");

// Debug-Klasse
class DionysosDebug {
    private $isLocal;
    private $pdoSqlite = null;
    private $pdoMysql = null;
    private $errors = [];
    private $warnings = [];
    private $infos = [];
    private $startTime;
    private $requestInfo = [];
    private $databaseInfo = [];
    private $routeInfo = [];
    private $systemInfo = [];
    
    public function __construct() {
        $this->startTime = microtime(true);
        $this->collectRequestInfo();
        $this->detectEnvironment();
        $this->collectSystemInfo();
        $this->testDatabaseConnections();
        $this->analyzeRouting();
    }
    
    private function collectRequestInfo() {
        $this->requestInfo = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'None',
            'time' => date('Y-m-d H:i:s'),
        ];
    }
    
    private function detectEnvironment() {
        $this->isLocal = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost';
        $this->systemInfo['environment'] = $this->isLocal ? 'Lokal (localhost)' : 'Produktion';
    }

    private function collectSystemInfo() {
        $this->systemInfo['php_version'] = phpversion();
        $this->systemInfo['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
        $this->systemInfo['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
        $this->systemInfo['memory_limit'] = ini_get('memory_limit');
        $this->systemInfo['max_execution_time'] = ini_get('max_execution_time') . ' seconds';
        $this->systemInfo['post_max_size'] = ini_get('post_max_size');
        $this->systemInfo['upload_max_filesize'] = ini_get('upload_max_filesize');
        
        // Prüfen ob wichtige Erweiterungen geladen sind
        $this->systemInfo['extensions'] = [
            'pdo' => extension_loaded('pdo'),
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'gd' => extension_loaded('gd'),
            'mbstring' => extension_loaded('mbstring'),
        ];
    }
    
    private function testDatabaseConnections() {
        // SQLite-Verbindung testen
        try {
            $sqlitePath = __DIR__ . '/database.db';
            if (file_exists($sqlitePath)) {
                $this->pdoSqlite = new PDO("sqlite:$sqlitePath");
                $this->pdoSqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->databaseInfo['sqlite'] = [
                    'status' => 'Connected',
                    'file_exists' => true,
                    'file_size' => $this->formatBytes(filesize($sqlitePath)),
                    'file_permissions' => substr(sprintf('%o', fileperms($sqlitePath)), -4),
                    'writable' => is_writable($sqlitePath) ? 'Yes' : 'No',
                ];
                
                // Basis-Strukturprüfung
                $tables = $this->getTablesFromSqlite();
                $this->databaseInfo['sqlite']['tables'] = $tables;
                
                // Einträge in wichtigen Tabellen zählen
                $this->databaseInfo['sqlite']['record_counts'] = $this->countRecordsInTables($this->pdoSqlite, $tables);
            } else {
                $this->databaseInfo['sqlite'] = [
                    'status' => 'Failed',
                    'error' => 'SQLite-Datenbankdatei nicht gefunden',
                    'file_exists' => false,
                ];
                $this->errors[] = 'SQLite-Datenbankdatei nicht gefunden: ' . $sqlitePath;
            }
        } catch (PDOException $e) {
            $this->databaseInfo['sqlite'] = [
                'status' => 'Failed',
                'error' => $e->getMessage(),
            ];
            $this->errors[] = 'SQLite-Verbindungsfehler: ' . $e->getMessage();
        }
        
        // MySQL-Verbindung testen
        try {
            $dbHost = "db************.hosting-data.io";
            $dbUser = "dbu***********";
            $dbPassword = "*************************";
            $dbName = "dbs**********";
            
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            $this->pdoMysql = new PDO($dsn, $dbUser, $dbPassword);
            $this->pdoMysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->databaseInfo['mysql'] = [
                'status' => 'Connected',
                'host' => $dbHost,
                'database' => $dbName,
                'user' => $dbUser,
            ];
            
            // Basis-Strukturprüfung
            $tables = $this->getTablesFromMysql();
            $this->databaseInfo['mysql']['tables'] = $tables;
            
            // Einträge in wichtigen Tabellen zählen
            $this->databaseInfo['mysql']['record_counts'] = $this->countRecordsInTables($this->pdoMysql, $tables);
            
        } catch (PDOException $e) {
            $this->databaseInfo['mysql'] = [
                'status' => 'Failed',
                'error' => $e->getMessage(),
            ];
            $this->errors[] = 'MySQL-Verbindungsfehler: ' . $e->getMessage();
        }
        
        // Vergleich der Tabellen zwischen SQLite und MySQL
        if (isset($this->databaseInfo['sqlite']['tables']) && isset($this->databaseInfo['mysql']['tables'])) {
            $this->compareDatabases();
        }
    }
    
    private function getTablesFromSqlite() {
        try {
            $stmt = $this->pdoSqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->errors[] = 'Fehler beim Abrufen der SQLite-Tabellen: ' . $e->getMessage();
            return [];
        }
    }
    
    private function getTablesFromMysql() {
        try {
            $stmt = $this->pdoMysql->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->errors[] = 'Fehler beim Abrufen der MySQL-Tabellen: ' . $e->getMessage();
            return [];
        }
    }
    
    private function countRecordsInTables($pdo, $tables) {
        $counts = [];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                $counts[$table] = $stmt->fetchColumn();
            } catch (PDOException $e) {
                $counts[$table] = 'Fehler: ' . $e->getMessage();
            }
        }
        return $counts;
    }
    
    private function compareDatabases() {
        $sqliteTables = $this->databaseInfo['sqlite']['tables'];
        $mysqlTables = $this->databaseInfo['mysql']['tables'];
        
        $missingInSqlite = array_diff($mysqlTables, $sqliteTables);
        $missingInMysql = array_diff($sqliteTables, $mysqlTables);
        
        if (!empty($missingInSqlite)) {
            $this->warnings[] = 'Tabellen in MySQL aber nicht in SQLite: ' . implode(', ', $missingInSqlite);
        }
        
        if (!empty($missingInMysql)) {
            $this->warnings[] = 'Tabellen in SQLite aber nicht in MySQL: ' . implode(', ', $missingInMysql);
        }
        
        // Gemeinsame Tabellen prüfen
        $commonTables = array_intersect($sqliteTables, $mysqlTables);
        $this->databaseInfo['comparison'] = [
            'common_tables' => count($commonTables),
            'missing_in_sqlite' => $missingInSqlite,
            'missing_in_mysql' => $missingInMysql,
        ];
        
        // Struktur einiger wichtiger Tabellen vergleichen
        $this->compareTableStructures($commonTables);
    }
    
    private function compareTableStructures($tables) {
        $structuralDifferences = [];
        
        foreach ($tables as $table) {
            // SQLite Spalten abrufen
            try {
                $sqliteStmt = $this->pdoSqlite->query("PRAGMA table_info($table)");
                $sqliteColumns = [];
                while ($row = $sqliteStmt->fetch(PDO::FETCH_ASSOC)) {
                    $sqliteColumns[$row['name']] = [
                        'type' => $row['type'],
                        'notnull' => $row['notnull'],
                    ];
                }
                
                // MySQL Spalten abrufen
                $mysqlStmt = $this->pdoMysql->query("DESCRIBE `$table`");
                $mysqlColumns = [];
                while ($row = $mysqlStmt->fetch(PDO::FETCH_ASSOC)) {
                    $mysqlColumns[$row['Field']] = [
                        'type' => $row['Type'],
                        'null' => $row['Null'] === 'NO' ? 1 : 0,
                    ];
                }
                
                // Spalten vergleichen
                $missingInSqlite = array_diff(array_keys($mysqlColumns), array_keys($sqliteColumns));
                $missingInMysql = array_diff(array_keys($sqliteColumns), array_keys($mysqlColumns));
                
                if (!empty($missingInSqlite) || !empty($missingInMysql)) {
                    $structuralDifferences[$table] = [
                        'missing_in_sqlite' => $missingInSqlite,
                        'missing_in_mysql' => $missingInMysql,
                    ];
                }
            } catch (PDOException $e) {
                $this->warnings[] = "Konnte Struktur der Tabelle '$table' nicht vergleichen: " . $e->getMessage();
            }
        }
        
        $this->databaseInfo['structural_differences'] = $structuralDifferences;
    }
    
    private function analyzeRouting() {
        // Router-Klasse analysieren
        $routerPath = __DIR__ . '/src/Router.php';
        if (file_exists($routerPath)) {
            $this->routeInfo['router_file'] = 'Gefunden';
            
            // Routerdatei parsen
            $routerContent = file_get_contents($routerPath);
            
            // Einfache Regex-Suche nach Routen
            preg_match_all("/'([^']+)'\s*=>\s*([^,]+)/", $routerContent, $matches, PREG_SET_ORDER);
            
            $routes = [];
            foreach ($matches as $match) {
                if (isset($match[1]) && isset($match[2])) {
                    $routes[$match[1]] = trim($match[2]);
                }
            }
            
            $this->routeInfo['defined_routes'] = $routes;
            
            // Aktuelle Route identifizieren
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $uri = strtok($uri, '?');  // Query-String entfernen
            
            $this->routeInfo['current_uri'] = $uri;
            
            // Prüfen ob die aktuelle Route definiert ist
            $routeFound = false;
            foreach ($routes as $pattern => $handler) {
                // Einfacher String-Vergleich
                if ($pattern === $uri) {
                    $this->routeInfo['matched_route'] = [
                        'pattern' => $pattern,
                        'handler' => $handler,
                        'match_type' => 'exact'
                    ];
                    $routeFound = true;
                    break;
                }
                
                // Regex-Muster (sehr einfache Implementierung)
                if (strpos($pattern, ':') !== false) {
                    $regexPattern = preg_replace('/:([^\/]+)/', '([^/]+)', $pattern);
                    $regexPattern = str_replace('/', '\/', $regexPattern);
                    if (preg_match('/^' . $regexPattern . '$/', $uri)) {
                        $this->routeInfo['matched_route'] = [
                            'pattern' => $pattern,
                            'handler' => $handler,
                            'match_type' => 'regex'
                        ];
                        $routeFound = true;
                        break;
                    }
                }
            }
            
            if (!$routeFound) {
                $this->routeInfo['route_match'] = 'Keine passende Route gefunden';
                $this->warnings[] = 'Die aktuelle URI "' . $uri . '" passt zu keiner definierten Route';
            }
            
        } else {
            $this->routeInfo['router_file'] = 'Nicht gefunden';
            $this->errors[] = 'Router-Datei nicht gefunden: ' . $routerPath;
        }
        
        // Prüfen ob index.php korrekt eingerichtet ist
        $indexPath = __DIR__ . '/public/index.php';
        if (file_exists($indexPath)) {
            $this->routeInfo['index_file'] = 'Gefunden';
            
            // Einfache Prüfung auf Router-Verwendung
            $indexContent = file_get_contents($indexPath);
            if (strpos($indexContent, 'Router') !== false) {
                $this->routeInfo['router_usage'] = 'Router wird in index.php verwendet';
            } else {
                $this->routeInfo['router_usage'] = 'Router wird möglicherweise nicht in index.php verwendet';
                $this->warnings[] = 'Router-Klasse scheint nicht in index.php verwendet zu werden';
            }
        } else {
            $this->routeInfo['index_file'] = 'Nicht gefunden';
            $this->errors[] = 'index.php nicht gefunden: ' . $indexPath;
        }
    }
    
    private function checkErrorLogs() {
        // PHP-Fehlerlog prüfen
        $errorLogPath = ini_get('error_log');
        if ($errorLogPath && file_exists($errorLogPath)) {
            $this->infos[] = 'PHP-Fehlerlog gefunden: ' . $errorLogPath;
            
            // Letzte Zeilen des Fehlerprotokolls abrufen
            $errorLog = $this->getTailOfFile($errorLogPath, 20);
            if (!empty($errorLog)) {
                $this->systemInfo['recent_errors'] = $errorLog;
            }
        } else {
            $this->warnings[] = 'PHP-Fehlerlog nicht gefunden oder nicht konfiguriert';
        }
        
        // Apache-Fehlerlog prüfen (typischer Pfad für xampp)
        $apacheLogPath = 'c:/xampp/apache/logs/error.log';
        if (file_exists($apacheLogPath)) {
            $this->infos[] = 'Apache-Fehlerlog gefunden: ' . $apacheLogPath;
            
            // Letzte Zeilen des Fehlerprotokolls abrufen
            $apacheLog = $this->getTailOfFile($apacheLogPath, 20);
            if (!empty($apacheLog)) {
                $this->systemInfo['apache_errors'] = $apacheLog;
            }
        }
    }
    
    private function getTailOfFile($file, $lines) {
        $result = [];
        
        try {
            $f = fopen($file, 'r');
            if ($f) {
                $fileSize = filesize($file);
                
                // Zu große Dateien nicht vollständig lesen
                if ($fileSize > 1048576) { // 1MB
                    fseek($f, -1048576, SEEK_END); // Die letzten ~1MB lesen
                    fgets($f); // Die erste unvollständige Zeile überspringen
                }
                
                $buffer = [];
                while (!feof($f)) {
                    $buffer[] = fgets($f);
                    if (count($buffer) > $lines) {
                        array_shift($buffer);
                    }
                }
                
                fclose($f);
                $result = $buffer;
            }
        } catch (Exception $e) {
            $this->warnings[] = 'Fehler beim Lesen der Logdatei: ' . $e->getMessage();
        }
        
        return $result;
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    public function renderDebugOutput() {
        $executionTime = round((microtime(true) - $this->startTime) * 1000, 2);
        
        // Fehler und Warnungen sammeln
        $this->checkErrorLogs();
        
        // CSS für die Debug-Seite
        echo $this->getDebugStyles();
        
        // Debug-Header
        echo '<div class="debug-container">';
        echo '<h1>Dionysos Website Debug Tool</h1>';
        echo '<div class="debug-time">Debug ausgeführt in ' . $executionTime . ' ms</div>';
        
        // Zusammenfassung
        echo '<div class="debug-summary">';
        echo '<div class="status-item"><span class="label">Umgebung:</span> ' . $this->systemInfo['environment'] . '</div>';
        
        // SQLite Status
        if (isset($this->databaseInfo['sqlite'])) {
            $sqliteStatus = $this->databaseInfo['sqlite']['status'] === 'Connected' ? 
                '<span class="status-ok">Verbunden</span>' : 
                '<span class="status-error">Fehler</span>';
            echo '<div class="status-item"><span class="label">SQLite:</span> ' . $sqliteStatus . '</div>';
        }
        
        // MySQL Status
        if (isset($this->databaseInfo['mysql'])) {
            $mysqlStatus = $this->databaseInfo['mysql']['status'] === 'Connected' ? 
                '<span class="status-ok">Verbunden</span>' : 
                '<span class="status-error">Fehler</span>';
            echo '<div class="status-item"><span class="label">MySQL:</span> ' . $mysqlStatus . '</div>';
        }
        
        // Router Status
        $routerStatus = isset($this->routeInfo['matched_route']) ? 
            '<span class="status-ok">Route gefunden</span>' : 
            '<span class="status-error">Keine Route</span>';
        echo '<div class="status-item"><span class="label">Router:</span> ' . $routerStatus . '</div>';
        
        // Fehler und Warnungen Zähler
        echo '<div class="status-item"><span class="label">Fehler:</span> <span class="' . 
            (count($this->errors) > 0 ? 'status-error' : 'status-ok') . '">' . count($this->errors) . '</span></div>';
        echo '<div class="status-item"><span class="label">Warnungen:</span> <span class="' . 
            (count($this->warnings) > 0 ? 'status-warning' : 'status-ok') . '">' . count($this->warnings) . '</span></div>';
        echo '</div>';
        
        // Fehler und Warnungen
        if (!empty($this->errors) || !empty($this->warnings)) {
            echo '<div class="debug-section">';
            echo '<h2>Fehler und Warnungen</h2>';
            
            if (!empty($this->errors)) {
                echo '<div class="debug-errors">';
                echo '<h3>Fehler</h3>';
                echo '<ul>';
                foreach ($this->errors as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            if (!empty($this->warnings)) {
                echo '<div class="debug-warnings">';
                echo '<h3>Warnungen</h3>';
                echo '<ul>';
                foreach ($this->warnings as $warning) {
                    echo '<li>' . htmlspecialchars($warning) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Request-Informationen
        echo '<div class="debug-section">';
        echo '<h2>Request-Informationen</h2>';
        echo '<table class="debug-table">';
        foreach ($this->requestInfo as $key => $value) {
            echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
        
        // Routing-Informationen
        echo '<div class="debug-section">';
        echo '<h2>Routing-Informationen</h2>';
        
        echo '<h3>Aktuelle URI</h3>';
        echo '<pre>' . htmlspecialchars($this->routeInfo['current_uri']) . '</pre>';
        
        if (isset($this->routeInfo['matched_route'])) {
            echo '<h3>Übereinstimmende Route</h3>';
            echo '<table class="debug-table">';
            echo '<tr><td>Pattern</td><td>' . htmlspecialchars($this->routeInfo['matched_route']['pattern']) . '</td></tr>';
            echo '<tr><td>Handler</td><td>' . htmlspecialchars($this->routeInfo['matched_route']['handler']) . '</td></tr>';
            echo '<tr><td>Match Type</td><td>' . htmlspecialchars($this->routeInfo['matched_route']['match_type']) . '</td></tr>';
            echo '</table>';
        } else {
            echo '<div class="error-message">Keine übereinstimmende Route gefunden!</div>';
        }
        
        if (isset($this->routeInfo['defined_routes']) && !empty($this->routeInfo['defined_routes']) && is_array($this->routeInfo['defined_routes'])) {
            echo '<h3>Alle definierten Routen</h3>';
            echo '<div class="scroll-container">';
            echo '<table class="debug-table">';
            echo '<tr><th>Pattern</th><th>Handler</th></tr>';
            foreach ($this->routeInfo['defined_routes'] as $pattern => $handler) {
                echo '<tr><td>' . htmlspecialchars($pattern) . '</td><td>' . htmlspecialchars($handler) . '</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Datenbank-Informationen
        echo '<div class="debug-section">';
        echo '<h2>Datenbank-Informationen</h2>';
        
        // SQLite
        if (isset($this->databaseInfo['sqlite'])) {
            echo '<h3>SQLite</h3>';
            if ($this->databaseInfo['sqlite']['status'] === 'Connected') {
                echo '<table class="debug-table">';
                foreach ($this->databaseInfo['sqlite'] as $key => $value) {
                    if ($key !== 'tables' && $key !== 'record_counts') {
                        echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars(is_bool($value) ? ($value ? 'true' : 'false') : $value) . '</td></tr>';
                    }
                }
                echo '</table>';
                
                if (isset($this->databaseInfo['sqlite']['tables'])) {
                    echo '<h4>SQLite Tabellen</h4>';
                    echo '<div class="scroll-container">';
                    echo '<table class="debug-table">';
                    echo '<tr><th>Tabelle</th><th>Datensätze</th></tr>';
                    foreach ($this->databaseInfo['sqlite']['tables'] as $table) {
                        $count = $this->databaseInfo['sqlite']['record_counts'][$table] ?? '?';
                        echo '<tr><td>' . htmlspecialchars($table) . '</td><td>' . htmlspecialchars($count) . '</td></tr>';
                    }
                    echo '</table>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error-message">SQLite-Verbindung fehlgeschlagen: ' . htmlspecialchars($this->databaseInfo['sqlite']['error']) . '</div>';
            }
        }
        
        // MySQL
        if (isset($this->databaseInfo['mysql'])) {
            echo '<h3>MySQL</h3>';
            if ($this->databaseInfo['mysql']['status'] === 'Connected') {
                echo '<table class="debug-table">';
                foreach ($this->databaseInfo['mysql'] as $key => $value) {
                    if ($key !== 'tables' && $key !== 'record_counts') {
                        echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars(is_bool($value) ? ($value ? 'true' : 'false') : $value) . '</td></tr>';
                    }
                }
                echo '</table>';
                
                if (isset($this->databaseInfo['mysql']['tables'])) {
                    echo '<h4>MySQL Tabellen</h4>';
                    echo '<div class="scroll-container">';
                    echo '<table class="debug-table">';
                    echo '<tr><th>Tabelle</th><th>Datensätze</th></tr>';
                    foreach ($this->databaseInfo['mysql']['tables'] as $table) {
                        $count = $this->databaseInfo['mysql']['record_counts'][$table] ?? '?';
                        echo '<tr><td>' . htmlspecialchars($table) . '</td><td>' . htmlspecialchars($count) . '</td></tr>';
                    }
                    echo '</table>';
                    echo '</div>';
                }
            } else {
                echo '<div class="error-message">MySQL-Verbindung fehlgeschlagen: ' . htmlspecialchars($this->databaseInfo['mysql']['error']) . '</div>';
            }
        }
        
        // Datenbankvergleich
        if (isset($this->databaseInfo['comparison'])) {
            echo '<h3>Datenbankvergleich</h3>';
            
            echo '<p><strong>Gemeinsame Tabellen:</strong> ' . $this->databaseInfo['comparison']['common_tables'] . '</p>';
            
            if (!empty($this->databaseInfo['comparison']['missing_in_sqlite'])) {
                echo '<p><strong>Tabellen fehlen in SQLite:</strong> ' . implode(', ', $this->databaseInfo['comparison']['missing_in_sqlite']) . '</p>';
            }
            
            if (!empty($this->databaseInfo['comparison']['missing_in_mysql'])) {
                echo '<p><strong>Tabellen fehlen in MySQL:</strong> ' . implode(', ', $this->databaseInfo['comparison']['missing_in_mysql']) . '</p>';
            }
            
            if (isset($this->databaseInfo['structural_differences']) && !empty($this->databaseInfo['structural_differences'])) {
                echo '<h4>Strukturelle Unterschiede</h4>';
                echo '<ul>';
                foreach ($this->databaseInfo['structural_differences'] as $table => $differences) {
                    echo '<li><strong>' . htmlspecialchars($table) . '</strong>';
                    echo '<ul>';
                    if (!empty($differences['missing_in_sqlite'])) {
                        echo '<li>Spalten fehlen in SQLite: ' . htmlspecialchars(implode(', ', $differences['missing_in_sqlite'])) . '</li>';
                    }
                    if (!empty($differences['missing_in_mysql'])) {
                        echo '<li>Spalten fehlen in MySQL: ' . htmlspecialchars(implode(', ', $differences['missing_in_mysql'])) . '</li>';
                    }
                    echo '</ul>';
                    echo '</li>';
                }
                echo '</ul>';
            }
        }
        
        echo '</div>';
        
        // System-Informationen
        echo '<div class="debug-section">';
        echo '<h2>System-Informationen</h2>';
        
        echo '<h3>PHP & Server</h3>';
        echo '<table class="debug-table">';
        foreach ($this->systemInfo as $key => $value) {
            if ($key !== 'extensions' && $key !== 'recent_errors' && $key !== 'apache_errors') {
                echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars(is_bool($value) ? ($value ? 'true' : 'false') : $value) . '</td></tr>';
            }
        }
        echo '</table>';
        
        if (isset($this->systemInfo['extensions']) && is_array($this->systemInfo['extensions'])) {
            echo '<h3>PHP-Erweiterungen</h3>';
            echo '<table class="debug-table">';
            foreach ($this->systemInfo['extensions'] as $ext => $loaded) {
                $status = $loaded ? '<span class="status-ok">Geladen</span>' : '<span class="status-error">Nicht geladen</span>';
                echo '<tr><td>' . htmlspecialchars($ext) . '</td><td>' . $status . '</td></tr>';
            }
            echo '</table>';
        }
        
        if (isset($this->systemInfo['recent_errors']) && !empty($this->systemInfo['recent_errors']) && is_array($this->systemInfo['recent_errors'])) {
            echo '<h3>Letzte PHP-Fehler</h3>';
            echo '<div class="log-container">';
            echo '<pre>';
            foreach ($this->systemInfo['recent_errors'] as $line) {
                echo htmlspecialchars($line);
            }
            echo '</pre>';
            echo '</div>';
        }
        
        if (isset($this->systemInfo['apache_errors']) && !empty($this->systemInfo['apache_errors']) && is_array($this->systemInfo['apache_errors'])) {
            echo '<h3>Letzte Apache-Fehler</h3>';
            echo '<div class="log-container">';
            echo '<pre>';
            foreach ($this->systemInfo['apache_errors'] as $line) {
                echo htmlspecialchars($line);
            }
            echo '</pre>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Session-Informationen
        echo '<div class="debug-section">';
        echo '<h2>Session-Informationen</h2>';
        
        if (!empty($_SESSION)) {
            echo '<div class="scroll-container">';
            echo '<table class="debug-table">';
            echo '<tr><th>Schlüssel</th><th>Wert</th></tr>';
            foreach ($_SESSION as $key => $value) {
                echo '<tr><td>' . htmlspecialchars($key) . '</td><td>';
                echo $this->formatVariableContent($value);
                echo '</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        } else {
            echo '<p>Keine Session-Daten vorhanden.</p>';
        }
        
        echo '</div>';
        
        // Debug-Aktionen
        echo '<div class="debug-section">';
        echo '<h2>Debug-Aktionen</h2>';
        echo '<div class="action-buttons">';
        echo '<a href="' . $_SERVER['PHP_SELF'] . '" class="debug-button">Debug aktualisieren</a>';
        echo '<a href="/" class="debug-button">Zur Startseite</a>';
        echo '<a href="/admin" class="debug-button">Zum Admin-Bereich</a>';
        echo '</div>';
        echo '</div>';
        
        echo '<footer class="debug-footer">';
        echo 'Debug-Tool für Dionysos-Website-v2 &copy; ' . date('Y');
        echo '</footer>';
        echo '</div>';
    }
    
    private function formatVariableContent($var) {
        if (is_null($var)) {
            return '<span class="null-value">NULL</span>';
        } elseif (is_bool($var)) {
            return $var ? '<span class="bool-value">true</span>' : '<span class="bool-value">false</span>';
        } elseif (is_string($var)) {
            if (strlen($var) > 100) {
                return '<span class="string-value">"' . htmlspecialchars(substr($var, 0, 100)) . '..."</span> <span class="string-length">(' . strlen($var) . ' Zeichen)</span>';
            } else {
                return '<span class="string-value">"' . htmlspecialchars($var) . '"</span>';
            }
        } elseif (is_int($var) || is_float($var)) {
            return '<span class="number-value">' . $var . '</span>';
        } elseif (is_array($var)) {
            $count = count($var);
            if ($count > 0) {
                return '<span class="array-value">Array (' . $count . ')</span>';
            } else {
                return '<span class="array-value">Array (leer)</span>';
            }
        } elseif (is_object($var)) {
            return '<span class="object-value">Objekt: ' . get_class($var) . '</span>';
        } else {
            return htmlspecialchars(gettype($var));
        }
    }
    
    private function getDebugStyles() {
        return '<style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f5f5f5;
                padding: 20px;
            }
            .debug-container {
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
                border-bottom: 2px solid #f0f0f0;
                padding-bottom: 10px;
            }
            h2 {
                color: #555;
                margin: 15px 0;
                border-bottom: 1px solid #f0f0f0;
                padding-bottom: 5px;
            }
            h3 {
                color: #666;
                margin: 10px 0;
            }
            h4 {
                color: #777;
                margin: 8px 0;
            }
            .debug-time {
                color: #888;
                font-size: 0.9em;
                margin-bottom: 20px;
            }
            .debug-summary {
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                margin-bottom: 20px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 5px;
            }
            .status-item {
                padding: 8px 12px;
                border-radius: 4px;
                background: white;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .label {
                font-weight: bold;
                margin-right: 5px;
            }
            .status-ok {
                color: #2e7d32;
            }
            .status-warning {
                color: #f57c00;
            }
            .status-error {
                color: #d32f2f;
            }
            .debug-section {
                margin-bottom: 30px;
                padding: 15px;
                background: white;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            .debug-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }
            .debug-table th, .debug-table td {
                padding: 8px 10px;
                border: 1px solid #e0e0e0;
                text-align: left;
            }
            .debug-table th {
                background: #f5f5f5;
                font-weight: bold;
            }
            .debug-table tr:nth-child(even) {
                background: #fafafa;
            }
            .debug-errors, .debug-warnings {
                margin: 10px 0;
                padding: 10px;
                border-radius: 4px;
            }
            .debug-errors {
                background: #ffebee;
                border-left: 4px solid #d32f2f;
            }
            .debug-warnings {
                background: #fff8e1;
                border-left: 4px solid #f57c00;
            }
            .debug-errors h3, .debug-warnings h3 {
                margin-top: 0;
            }
            .scroll-container {
                max-height: 300px;
                overflow-y: auto;
                margin: 10px 0;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
            }
            .log-container {
                max-height: 250px;
                overflow-y: auto;
                background: #2b2b2b;
                color: #f8f8f8;
                padding: 10px;
                border-radius: 4px;
                font-family: monospace;
                margin: 10px 0;
            }
            .log-container pre {
                margin: 0;
                white-space: pre-wrap;
            }
            .error-message {
                color: #d32f2f;
                padding: 10px;
                background: #ffebee;
                border-radius: 4px;
                margin: 10px 0;
            }
            .action-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            .debug-button {
                display: inline-block;
                padding: 8px 16px;
                background: #2196f3;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                transition: background 0.3s;
            }
            .debug-button:hover {
                background: #1976d2;
            }
            .debug-footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 1px solid #e0e0e0;
                color: #777;
                text-align: center;
                font-size: 0.9em;
            }
            .null-value { color: #999; font-style: italic; }
            .bool-value { color: #0d47a1; font-weight: bold; }
            .string-value { color: #2e7d32; }
            .number-value { color: #d32f2f; }
            .array-value { color: #6a1b9a; }
            .object-value { color: #0277bd; }
            .string-length { color: #999; font-size: 0.9em; }
        </style>';
    }
}

// Haupt-Ausführungspunkt
try {
    $debugger = new DionysosDebug();
    $debugger->renderDebugOutput();
} catch (Exception $e) {
    echo '<h1>Debug-Tool Fehler</h1>';
    echo '<p>Es ist ein unerwarteter Fehler aufgetreten: ' . $e->getMessage() . '</p>';
}
