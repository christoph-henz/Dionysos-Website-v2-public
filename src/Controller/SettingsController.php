<?php

namespace Dionysosv2\Controller;

use Dionysosv2\Models\Settings;
use PDO;

class SettingsController
{
    private Settings $settings;
    private PDO $database;
    
    public function __construct()
    {
        // Umgebung prüfen
        $isLocal = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost';

        if ($isLocal) {
            // Lokale Umgebung: SQLite verwenden
            $this->database = new PDO('sqlite:' . __DIR__ . '/../../database.db');
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            // Live-Umgebung: MySQL/MariaDB verwenden
            $dbHost = "db************.hosting-data.io";
            $dbUser = "dbu***********";
            $dbPassword = "*************************";
            $dbName = "dbs**********";

            // MySQL-Verbindung mit PDO erstellen
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            $this->database = new PDO($dsn, $dbUser, $dbPassword);
            
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // MySQL-spezifische Einstellung: Emulation von Prepared Statements ausschalten
            $this->database->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        
        $this->settings = new Settings($this->database);
    }
    
    /**
     * Holt eine einzelne Einstellung
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings->get($key, $default);
    }
    
    /**
     * Setzt eine Einstellung
     */
    public function setSetting(string $key, $value, string $type = 'string'): bool
    {
        return $this->settings->set($key, $value, $type);
    }
    
    /**
     * Holt alle Einstellungen einer Kategorie
     */
    public function getSettingsByCategory(string $category): array
    {
        return $this->settings->getByCategory($category);
    }
    
    /**
     * Prüft ob das Restaurant geöffnet ist
     */
    public function isRestaurantOpen(): bool
    {
        return $this->settings->isOpen();
    }
    
    /**
     * Holt die kompletten Öffnungszeiten
     */
    public function getOpeningHours(): array
    {
        return $this->settings->getOpeningHours();
    }
    
    /**
     * Prüft ob ein Feature aktiviert ist
     */
    public function isFeatureEnabled(string $feature): bool
    {
        return $this->settings->isFeatureEnabled($feature);
    }
    
    /**
     * Holt Restaurant-Informationen für die Anzeige
     */
    public function getRestaurantInfo(): array
    {
        return [
            'name' => $this->getSetting('restaurant_name'),
            'phone' => $this->getSetting('restaurant_phone'),
            'email' => $this->getSetting('restaurant_email'),
            'address' => $this->getSetting('restaurant_address'),
            'is_open' => $this->isRestaurantOpen(),
            'opening_hours' => $this->getOpeningHours()
        ];
    }
    
    /**
     * Holt Bestellsystem-Einstellungen
     */
    public function getOrderSettings(): array
    {
        return [
            'enabled' => $this->isFeatureEnabled('order_system'),
            'delivery_enabled' => $this->isFeatureEnabled('delivery'),
            'pickup_enabled' => $this->isFeatureEnabled('pickup'),
            'min_amount' => (float) $this->getSetting('order_min_amount', 0),
            'delivery_fee' => (float) $this->getSetting('delivery_fee', 0),
            'preparation_time' => $this->getSetting('preparation_time', 30)
        ];
    }
    
    /**
     * Holt Reservierungs-Einstellungen
     */
    public function getReservationSettings(): array
    {
        return [
            'enabled' => $this->isFeatureEnabled('reservation_system'),
            'advance_days' => $this->getSetting('reservation_advance_days', 30),
            'min_duration' => $this->getSetting('reservation_min_duration', 120),
            'max_party_size' => $this->getSetting('reservation_max_party_size', 20),
            'time_slots' => $this->settings->getReservationTimeSlots()
        ];
    }
    
    /**
     * Holt Zeitslots für ein bestimmtes Datum
     * @param string|null $date Datum im Format 'Y-m-d' oder null für heute
     */
    public function getTimeSlotsForDate(?string $date = null): array
    {
        return $this->settings->getReservationTimeSlotsForDate($date);
    }
    
    /**
     * Verarbeitet Einstellungs-Updates (für Admin-Bereich)
     */
    public function updateSettings(array $data): array
    {
        $updated = [];
        $errors = [];
        
        foreach ($data as $key => $value) {
            // Validierung je nach Einstellungstyp
            if (str_contains($key, '_enabled')) {
                $success = $this->setSetting($key, (bool) $value, 'boolean');
            } elseif (str_contains($key, '_amount') || str_contains($key, '_fee')) {
                $success = $this->setSetting($key, number_format((float) $value, 2), 'string');
            } elseif (str_contains($key, '_time') || str_contains($key, '_days')) {
                $success = $this->setSetting($key, (int) $value, 'integer');
            } elseif (str_contains($key, 'opening_hours_')) {
                $success = $this->setSetting($key, $value, 'json');
            } else {
                $success = $this->setSetting($key, $value, 'string');
            }
            
            if ($success) {
                $updated[] = $key;
            } else {
                $errors[] = $key;
            }
        }
        
        // Cache leeren nach Updates
        Settings::clearCache();
        
        return [
            'success' => empty($errors),
            'updated' => $updated,
            'errors' => $errors
        ];
    }
    
    /**
     * Generiert HTML für Öffnungszeiten-Anzeige
     */
    public function generateOpeningHoursDisplay(): string
    {
        $hours = $this->getOpeningHours();
        $dayNames = [
            'monday' => 'Montag',
            'tuesday' => 'Dienstag', 
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag'
        ];
        
        $html = '<div class="opening-hours">';
        
        foreach ($hours as $day => $schedule) {
            $html .= '<div class="day-schedule">';
            $html .= '<strong>' . $dayNames[$day] . ':</strong> ';
            
            if ($schedule['closed']) {
                $html .= '<span class="closed">Ruhetag</span>';
            } else {
                $html .= $schedule['open'] . ' - ' . $schedule['close'] . ' Uhr';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
