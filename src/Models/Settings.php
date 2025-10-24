<?php

namespace Dionysosv2\Models;

use PDO;
use Exception;

class Settings
{
    private PDO $database;
    private static array $cache = [];
    
    // Debug-Variablen für Datum/Zeit-Simulation
    private static ?string $debugDate = null;
    private static ?string $debugTime = null;
    
    public function __construct(PDO $database)
    {
        $this->database = $database;
    }
    
    /**
     * Setzt Debug-Datum für Tests (Format: Y-m-d)
     */
    public static function setDebugDate(?string $date): void
    {
        self::$debugDate = $date;
    }
    
    /**
     * Setzt Debug-Zeit für Tests (Format: H:i)
     */
    public static function setDebugTime(?string $time): void
    {
        self::$debugTime = $time;
    }
    
    /**
     * Setzt Debug-Datum und Zeit gleichzeitig
     */
    public static function setDebugDateTime(?string $date, ?string $time): void
    {
        // Debug-Werte in Session speichern für Persistenz zwischen Requests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['debug_date'] = $date;
        $_SESSION['debug_time'] = $time;
        
        // Auch in statischen Variablen für aktuellen Request setzen
        self::$debugDate = $date;
        self::$debugTime = $time;
        
        error_log("Debug-Zeit gesetzt: Datum=" . ($date ?: 'null') . ", Zeit=" . ($time ?: 'null'));
    }
    
    /**
     * Zurücksetzen aller Debug-Werte
     */
    public static function resetDebug(): void
    {
        // Aus Session entfernen
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['debug_date']);
        unset($_SESSION['debug_time']);
        
        // Statische Variablen zurücksetzen
        self::$debugDate = null;
        self::$debugTime = null;
        
        error_log("Debug-Zeit zurückgesetzt");
    }
    
    /**
     * Lädt Debug-Werte aus Session falls verfügbar
     */
    private static function loadDebugFromSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['debug_date'])) {
            self::$debugDate = $_SESSION['debug_date'];
        }
        
        if (isset($_SESSION['debug_time'])) {
            self::$debugTime = $_SESSION['debug_time'];
        }
    }
    
    /**
     * Holt das aktuelle Datum (mit Debug-Unterstützung)
     */
    private function getCurrentDate(): string
    {
        self::loadDebugFromSession();
        return self::$debugDate ?? date('Y-m-d');
    }
    
    /**
     * Holt die aktuelle Zeit (mit Debug-Unterstützung)
     */
    private function getCurrentTime(): string
    {
        self::loadDebugFromSession();
        return self::$debugTime ?? date('H:i');
    }
    
    /**
     * Holt den aktuellen Wochentag (mit Debug-Unterstützung)
     */
    private function getCurrentDayOfWeek(): string
    {
        self::loadDebugFromSession();
        if (self::$debugDate) {
            $timestamp = strtotime(self::$debugDate);
            return strtolower(date('l', $timestamp));
        }
        return strtolower(date('l'));
    }
    
    /**
     * Debug-Info anzeigen
     */
    public function getDebugInfo(): array
    {
        self::loadDebugFromSession();
        return [
            'debug_mode' => self::$debugDate !== null || self::$debugTime !== null,
            'debug_date' => self::$debugDate,
            'debug_time' => self::$debugTime,
            'current_date' => $this->getCurrentDate(),
            'current_time' => $this->getCurrentTime(),
            'current_day' => $this->getCurrentDayOfWeek(),
            'real_date' => date('Y-m-d'),
            'real_time' => date('H:i'),
            'real_day' => strtolower(date('l'))
        ];
    }
    
    /**
     * Holt eine Einstellung aus der Datenbank
     */
    public function get(string $key, $default = null)
    {
        // Cache prüfen
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        try {
            $stmt = $this->database->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                self::$cache[$key] = $default;
                return $default;
            }
            
            $value = $this->parseValue($result['setting_value'], $result['setting_type']);
            self::$cache[$key] = $value;
            
            return $value;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Setzt eine Einstellung in der Datenbank
     */
    public function set(string $key, $value, string $type = 'string'): bool
    {
        try {
            $formattedValue = $this->formatValue($value, $type);
            
            // Prüfen ob wir mit SQLite oder MySQL arbeiten
            $driver = $this->database->getAttribute(\PDO::ATTR_DRIVER_NAME);
            
            if ($driver === 'sqlite') {
                // SQLite Syntax
                $stmt = $this->database->prepare("
                    INSERT OR REPLACE INTO settings (setting_key, setting_value, setting_type, updated_at) 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $result = $stmt->execute([$key, $formattedValue, $type]);
            } else {
                // MySQL Syntax
                $stmt = $this->database->prepare("
                    INSERT INTO settings (setting_key, setting_value, setting_type, updated_at) 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE 
                    setting_value = ?, setting_type = ?, updated_at = CURRENT_TIMESTAMP
                ");
                $result = $stmt->execute([$key, $formattedValue, $type, $formattedValue, $type]);
            }
            
            // Cache aktualisieren
            self::$cache[$key] = $value;
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Holt alle Einstellungen einer Kategorie
     */
    public function getByCategory(string $category): array
    {
        try {
            $stmt = $this->database->prepare("
                SELECT setting_key, setting_value, setting_type, description 
                FROM settings 
                WHERE category = ? 
                ORDER BY setting_key
            ");
            $stmt->execute([$category]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = [
                    'value' => $this->parseValue($row['setting_value'], $row['setting_type']),
                    'description' => $row['description']
                ];
            }
            
            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Prüft ob das Restaurant zur aktuellen Zeit geöffnet ist
     */
    public function isOpen(): bool
    {
        $today = $this->getCurrentDayOfWeek();
        $currentTime = $this->getCurrentTime();
        
        $hours = $this->get("opening_hours_{$today}");
        
        if (!$hours || $hours['closed']) {
            return false;
        }
        
        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }
    
    /**
     * Prüft ob Bestellungen zur aktuellen Zeit angenommen werden
     * (2 Stunden vor Schließung wird das Bestellsystem deaktiviert)
     */
    public function isOrderingAvailable(): bool
    {
        $today = $this->getCurrentDayOfWeek();
        $currentTime = $this->getCurrentTime();
        
        $hours = $this->get("opening_hours_{$today}");
        
        if (!$hours || $hours['closed']) {
            return false;
        }
        
        // Berechne die Zeit 2 Stunden vor Schließung
        $closeTime = new \DateTime($hours['close']);
        $orderingCutoff = clone $closeTime;
        $orderingCutoff->sub(new \DateInterval('PT2H')); // 2 Stunden abziehen
        
        $currentDateTime = new \DateTime($currentTime);
        $openDateTime = new \DateTime($hours['open']);
        
        // Prüfe ob zwischen Öffnung und 2 Stunden vor Schließung
        return $currentDateTime >= $openDateTime && $currentDateTime <= $orderingCutoff;
    }
    
    /**
     * Holt die Öffnungszeiten für alle Wochentage
     */
    public function getOpeningHours(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];
        
        foreach ($days as $day) {
            $hours[$day] = $this->get("opening_hours_{$day}");
        }
        
        return $hours;
    }
    
    /**
     * Prüft ob ein System-Feature aktiviert ist
     */
    public function isFeatureEnabled(string $feature): bool
    {
        // Prüfe zuerst die direkte Einstellung (neues Format)
        $value = $this->get($feature, null);
        if ($value !== null) {
            return (bool) intval($value);
        }
        
        // Fallback: Prüfe mit "_enabled" Suffix (altes Format)
        return (bool) $this->get($feature . '_enabled', false);
    }
    
    /**
     * Holt verfügbare Reservierungs-Zeitslots
     */
    public function getReservationTimeSlots(): array
    {
        return $this->get('reservation_time_slots', []);
    }
    
    /**
     * Holt verfügbare Reservierungs-Zeitslots für einen bestimmten Wochentag
     * @param string|null $date Datum im Format 'Y-m-d' oder null für heute
     */
    public function getReservationTimeSlotsForDate(?string $date = null): array
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return []; // Ungültiges Datum
        }
        
        $dayOfWeek = date('w', $timestamp); // 0 = Sonntag, 1 = Montag, etc.
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $dayName = $dayNames[$dayOfWeek];
        
        $settingKey = 'reservation_time_slots_' . $dayName;
        $timeSlots = $this->get($settingKey, []);
        
        // Fallback auf generische Zeitslots falls keine spezifischen verfügbar
        if (empty($timeSlots)) {
            $timeSlots = $this->getReservationTimeSlots();
        }
        
        return $timeSlots;
    }
    
    /**
     * Cache leeren
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
    
    /**
     * Formatiert Werte für die Datenbank
     */
    private function formatValue($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'integer':
                return (string) (int) $value;
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
    
    /**
     * Parst Werte aus der Datenbank
     */
    private function parseValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }
}
