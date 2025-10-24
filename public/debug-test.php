<?php
// Debug-Test-Seite
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

try {
    echo "<h1>Debug-Zeit Test</h1>";
    
    // PDO für Settings
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database.db');
    $settings = new \Dionysosv2\Models\Settings($pdo);
    
    echo "<h2>Session-Informationen:</h2>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Debug-Datum in Session: " . ($_SESSION['debug_date'] ?? 'nicht gesetzt') . "</p>";
    echo "<p>Debug-Zeit in Session: " . ($_SESSION['debug_time'] ?? 'nicht gesetzt') . "</p>";
    
    echo "<h2>Debug-Informationen:</h2>";
    $debugInfo = $settings->getDebugInfo();
    echo "<pre>" . print_r($debugInfo, true) . "</pre>";
    
    echo "<h2>Aktionen:</h2>";
    echo "<a href='?action=set&date=2025-12-25&time=14:30'>🎄 Weihnachten 14:30 setzen</a><br>";
    echo "<a href='?action=set&time=22:00'>🌙 22:00 Uhr setzen</a><br>";
    echo "<a href='?action=reset'>🔄 Zurücksetzen</a><br>";
    echo "<a href='?'>🔄 Neu laden</a><br>";
    
    // Actions verarbeiten
    if (isset($_GET['action'])) {
        echo "<hr><h3>Aktion ausgeführt:</h3>";
        
        switch ($_GET['action']) {
            case 'set':
                $date = $_GET['date'] ?? null;
                $time = $_GET['time'] ?? null;
                \Dionysosv2\Models\Settings::setDebugDateTime($date, $time);
                echo "<p>✅ Debug-Zeit gesetzt: " . ($date ?: 'aktuell') . " " . ($time ?: 'aktuell') . "</p>";
                break;
                
            case 'reset':
                \Dionysosv2\Models\Settings::resetDebug();
                echo "<p>✅ Debug-Zeit zurückgesetzt</p>";
                break;
        }
        
        echo "<p><a href='?'>🔄 Ergebnis anzeigen</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Fehler:</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
