-- MySQL-kompatible Datenbankversion
-- Konvertiert aus SQLite init-db-sqlite.sql

-- Beginne mit Transaktion
START TRANSACTION;

-- Tabellendefinitionen

CREATE TABLE IF NOT EXISTS gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_id INT NOT NULL,
    display_order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(255),
    FOREIGN KEY (image_id)
        REFERENCES images(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_category (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,     -- Enum-Name z. B. 'Grill'
    label VARCHAR(255) NOT NULL            -- Anzeigename z. B. 'Spezialitäten vom Grill'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plu VARCHAR(50) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    FOREIGN KEY (category_id) REFERENCES article_category(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS article_option_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    option_group_id INT NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES article(id) ON DELETE CASCADE,
    FOREIGN KEY (option_group_id) REFERENCES option_groups(id) ON DELETE CASCADE,
    UNIQUE(article_id, option_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS option_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_required BOOLEAN DEFAULT FALSE,
    max_selections INT DEFAULT 1,
    min_selections INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_group_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price_modifier DECIMAL(10,2) DEFAULT 0.00,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (option_group_id) REFERENCES option_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    street VARCHAR(255) NOT NULL,
    number VARCHAR(50) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    city VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    created_on DATETIME NOT NULL,
    total_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    telegram_message_id INT,
    notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_item (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    article_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    options_json TEXT,
    FOREIGN KEY (invoice_id) REFERENCES invoice(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    guests INT NOT NULL CHECK (guests > 0),
    notes TEXT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    telegram_message_id INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_editable BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indizes erstellen

CREATE INDEX idx_gallery_order ON Gallery(display_order);
CREATE INDEX idx_reservation_date ON reservations(reservation_date);
CREATE INDEX idx_reservation_status ON reservations(status);
CREATE INDEX idx_setting_category ON settings(category);
CREATE INDEX idx_setting_key ON settings(setting_key);

-- Testdaten einfügen

INSERT INTO Images (id, name, created_at) VALUES 
(1, '04.jpg', '2025-07-29 14:29:24'),
(2, '4.jpg', '2025-07-29 14:36:02'),
(3, '1.jpg', '2025-07-29 14:36:04'),
(4, '2.jpg', '2025-07-29 14:36:05');

INSERT INTO Gallery (id, image_id, display_order, active, created_at, description) VALUES 
(1, 1, 0, 1, '2025-07-29 14:30:00', 'Kamin'),
(2, 2, 1, 1, '2025-07-29 14:37:07', 'Vorderer Gastraum'),
(3, 3, 2, 1, '2025-07-29 14:37:20', 'Theke');

INSERT INTO article_category (id, code, label) VALUES 
(1, 'Meze_Cold', 'Meze Kalt'),
(2, 'Meze_Warm', 'Meze Warm'),
(3, 'Supp_Salad', 'Beilagensalate'),
(4, 'Supplements', 'Beilagen'),
(5, 'Vegetarian', 'Vegetarische Hauptspeisen'),
(6, 'Salads', 'Salatteller '),
(7, 'Grill', 'Spezialitäten vom Grill'),
(8, 'Oven', 'Aus dem Ofen'),
(9, 'Skewers', 'Fleischspieße'),
(10, 'Plates', 'Unsere Grillteller'),
(11, 'Fish', 'Fischspezialitäten'),
(12, 'Dessert', 'Nachspeisen'),
(13, 'Pizza', 'Pizza'),
(14, 'Childs_Meal', 'Kinderkarte'),
(15, 'Special_Menu', 'Wochenkarte');

INSERT INTO article (id, plu, category_id, name, price, description) VALUES 
(1, '11', 1, 'Peperoni', 3.1, 'mit Knoblauch'),
(2, '12', 1, 'Schwarze Oliven', 3.1, '& Knoblauch aus Kalamata'),
(3, '13', 1, 'Grüne Oliven', 3.1, 'gefüllt mit Paprika & Knoblauch'),
(4, '14', 1, 'Original Griech. Schafskäse', 3.8, NULL),
(5, '15', 1, 'Melitzanosalata', 3.8, 'Auberginenpaste & Knoblauch'),
(6, '16', 1, 'Saziki', 3.8, 'Griech. Joghurt mit Gurken & Knoblauch'),
(7, '17', 1, 'Tirokafteri', 3.8, 'Schafskäsepaste scharf & Knoblauch'),
(8, '18', 1, 'Taramosalata', 3.8, 'Fischrogencreme'),
(9, '19', 1, 'Sardellen', 3.9, 'eingelegt & gesalzen'),
(10, '20', 1, 'Kalte Riesenbohnen', 3.7, 'mit Paprika & Knoblauch'),
(11, '21', 1, 'Oktupussalat', 4.2, 'eingelegt in Öl-Essig'),
(12, '52', 1, 'Weißbrot', 1.9, NULL),
(13, '26', 2, 'Gegrillte Peperoni', 4.2, 'mit Knoblauch'),
(14, '27', 2, 'Gegrillte Aubergine', 3.8, 'natur mit Olivenöl'),
(15, '28', 2, 'Gegrillte Zucchini', 3.8, 'natur mit Olivenöl'),
(16, '29', 2, 'Gegrillte Champignons', 3.8, 'natur mit Olivenöl'),
(17, '30', 2, 'Dolmadakia', 3.8, 'Weinblätter mit Reisfüllung'),
(18, '31', 2, 'Bruschetta', 3.9, 'mit Tomaten-Knoblauch Aufstrich'),
(19, '32', 2, 'Knoblauchbrot', 3.9, 'mit mediterranen Gewürzen'),
(20, '33', 2, 'Saganaki', 4.2, 'panierter Mischkäse'),
(21, '34', 2, 'Pitta', 3.2, 'gegrilltes Fladenbrot'),
(22, '22', 3, 'Gemischter Beilagensalat', 4.2, NULL),
(23, '23', 3, 'Krautsalat eingelegt', 3.9, NULL),
(24, '24', 3, 'Kl. Bauernsalat', 5.2, 'Tomate, Gurke, Schafskäse, Zwiebeln'),
(25, '45', 6, 'Gr. Bauernsalat', 11.4, 'Tomaten, Gurken, Zwiebeln, Schafskäse & Saziki'),
(26, '46', 6, 'Großer gemischter Salatteller', 14.4, 'mit gegrillter Hähnchenbrust'),
(27, '48', 6, 'Großer gemischter Salatteller', 14.2, 'mit Thunfisch und Ei'),
(28, '49', 6, 'Großer gemischter Salatteller', 13.4, 'mit gebratenen Champignons'),
(29, '50', 7, 'Argentinisches Rumpsteak', 24.4, '(ca. 220 g Rohgewicht) gegrillt mit Kräuterbutter dazu Bohnen & Steakhouse-Pommes'),
(30, '51', 7, 'Rinderleber', 14.2, 'gebraten mit Zwiebeln & Butter-Reis'),
(31, '54', 7, 'Lammspieß', 21.4, 'gegrillter Lammrücken mit Knoblauch, Bohnen & Steakhouse-Pommes'),
(32, '55', 7, 'Paidakia', 21.4, 'gegrillte Lammkoteletts mit Knoblauch, Bohnen & Steakhouse-Pommes'),
(33, '56', 7, 'Lammteller', 21.6, 'gegrillte Lammkoteletts & Lammrücken mit Knoblauch, Bohnen & Steakhouse-Pommes'),
(34, '57', 7, 'Lammhaxe', 19.2, 'aus dem Ofen mit Stifado-Zwiebeln & Brot'),
(35, '65', 7, 'Gyrosteller', 14.5, 'vom Schwein mit Bohnen, Steakhouse-Pommes & Saziki'),
(36, '66', 8, 'Gyros Metaxa', 15.5, 'vom Schwein in Metaxasoße & Pilzen, aus dem Ofen mit Käse überbacken & Steakhouse-Pommes'),
(37, '67', 8, 'Riganato', 15.8, 'Schweinesteak in Metaxasoße & Pilzen, aus dem Ofen mit Käse überbacken & Steakhouse-Pommes'),
(38, '69', 7, 'Bifteki', 15.6, 'gegrilltes Schweinehacksteak gefüllt mit Schafskäse dazu Bohnen & Steakhouse-Pommes'),
(39, '71', 7, 'Suwlakia', 14.6, 'gegrillte Schweinespieße dazu Bohnen & Steakhouse-Pommes'),
(40, '72', 7, 'Gegrillter Bauernspieß', 17.5, 'mageres Schweinefleisch dazu Bohnen & Steakhouse-Pommes'),
(41, '73', 7, 'Hähnchenspieße', 15.6, 'mit Bohnen & Steakhouse-Pommes'),
(42, '40', 5, 'Saganaki Natur', 10.9, 'Mischkäse im Steintopf dazu Tomate, Peperoni & Knoblauch'),
(43, '41', 5, 'Saganaki Paniert', 11.2, 'Mischkäse im Steintopf dazu Peperoni & Knoblauch'),
(44, '42', 5, 'Pikilia', 12.5, 'panierte Aubergine & Zucchini dazu Peperoni, Oliven, Saganaki & Saziki'),
(45, '43', 5, 'Gigantes', 10.9, 'Riesenbohnen im Steintopf mit Paprika, Schafskäse und Knoblauch'),
(47, '44', 5, 'Juwetzi', 11.5, 'griechische Nudeln mit Metaxa -oder Tomatensoße, Schafskäse und Edamer Käse überbacken'),
(48, '59', 10, 'Naki Teller', 17.8, 'Gyros, Suwlaki, Suzukaki dazu Bohnen, Saziki & Steakhouse-Pommes'),
(49, '60', 10, 'Vinka Teller', 19.8, 'Gyros, Suwlaki, Suzukaki, Hänchenspieß dazu Bohnen, Saziki & Steakhouse-Pommes'),
(50, '61', 10, 'Athina Teller', 19.8, 'Gyros, Suwlaki, Suzukaki, Leber dazu Bohnen, Saziki & Steakhouse-Pommes'),
(51, '62', 10, 'Dionys Teller', 21.6, 'Gyros, Suwlaki, Bifteki, Lammkotelett dazu Bohnen, Saziki & Steakhouse-Pommes'),
(52, '74', 11, 'Gavros', 14.2, 'in Maismehl panierte Sardellen dazu Saziki und Brot'),
(53, '75', 11, 'Panierte Babycalamari', 18.4, 'mit Knoblauchöl dazu Butter-Reis'),
(54, '76', 11, 'Gegrilltes Lachsfilet', 18.8, 'mit Knoblauchöl dazu Butter-Reis'),
(55, '1', 13, 'Pizza Margarita', 8.3, 'Tomatensoße, Käse'),
(56, '2', 13, 'Pizza Salami', 8.6, 'Tomatensoße, Käse, Salami'),
(57, '3', 13, 'Pizza Spezial', 10.4, 'Tomatensoße, Käse, Salami, Schinken, Paprika, Zwiebeln, Pilze'),
(58, '360', 14, 'Hähnchenspieß', 8.2, 'gegrillt mit Pommes'),
(59, '361', 14, 'Schweinespieß', 7.9, 'gegrillt mit Pommes'),
(60, '362', 14, 'Gyros vom Drehspieß', 8.7, 'mit Pommes und Saziki'),
(61, '363', 14, 'Suzukaki', 7.2, 'ungefülltes Hacksteak mit Pommes'),
(62, '364', 14, 'Bifteki', 8.5, 'gefülltes Hacksteak mit Schafskäse dazu Pommes'),
(63, '365', 14, 'Schnitzel', 8.2, 'paniert dazu Pommes'),
(64, '4', 13, 'Pizza Schinken Schafskäse', 11.3, 'Tomatensoße, Käse, Schafskäse, Schinken, Zwiebeln'),
(65, '5', 13, 'Pizza Thunfisch', 11.4, 'Tomatensoße, Käse, Thunfisch, Artischocken, Zwiebeln, Kapern'),
(66, '6', 13, 'Pizza Gyros', 12.4, 'Tomatensoße, Käse, Gyros, Schafskäse, Zwiebeln'),
(67, '7', 13, 'Pizza Veggie', 10.4, 'Tomatensoße, Käse, Pilze, Paprika, Zwiebeln, Mais & Tomaten'),
(68, '35', 4, 'Nudel', 3.7, NULL),
(69, '36', 4, 'Butterreis', 3.7, NULL),
(70, '37', 4, 'Portion Grüne Bohnen', 3.7, NULL),
(71, '38', 4, 'Portion Steakhouse – Pommes', 4.2, NULL),
(72, '39', 4, 'Portion Ajvar (Paprika Dip)', 2.4, NULL),
(73, '53', 4, 'Extra Metaxasoße', 3.9, NULL),
(74, '81', 12, 'Joghurt', 4.9, 'mit Honig und Walnüssen'),
(75, '82', 12, 'Galaktobureko', 6.8, 'warmer Grießauflauf im Blätterteig'),
(76, '83', 12, 'Souffle', 5.9, 'mit flüssigem Kern'),
(77, '506', 15, 'Akropolis Platte (für 2 Personen)', 41.8, '2x Suwlaki, 2x Lammkoteletts, 2x Schweinesteaks und Gyros, dazu Pommes, grüne Bohnen und Saziki'),
(78, '507', 15, 'Poseidon Platte (für 2 Personen)', 48.9, '2x Lachs, 2x Garnelenspieß und Calamari, dazu Reis und Beilagensalat');

-- Rechnungsdaten
INSERT INTO invoice (id, name, street, number, postal_code, city, email, phone, created_on, total_amount, tax_amount, status, telegram_message_id, notes) VALUES
(10, 'Christoph', '', '', '', '', 'christophhenz@gmail.com', '015227434327', '2025-08-03 23:16:30', 29.0, 5.51, 'pending', 9, '--Test 3--'),
(11, 'Christoph', '', '', '', '', 'christophhenz@gmail.com', '015227434327', '2025-08-04 00:03:39', 46.8, 8.892, 'cancelled', 10, '--Test 9--'),
(12, 'Christoph', '', '', '', '', 'christophhenz@gmail.com', '015227434327', '2025-08-04 00:09:49', 95.6, 18.164, 'finished', 11, '--Test 10--');

-- Bestellte Artikel
INSERT INTO order_item (id, invoice_id, article_id, quantity, total_price, options_json) VALUES
(22, 10, 2, 1, 3.1, '[]'),
(23, 10, 29, 1, 25.9, '[{"name":"Rare","price":0},{"name":"Mit Zwiebeln","price":1.5}]'),
(24, 11, 2, 1, 3.1, '[]'),
(25, 11, 29, 1, 25.9, '[{"name":"Medium Rare","price":0},{"name":"Mit Zwiebeln","price":1.5}]'),
(26, 11, 64, 1, 17.8, '[{"name":"Kapern","price":1.5},{"name":"Paprika","price":1.5},{"name":"Thunfisch","price":2.5},{"name":"Zwiebeln","price":1}]'),
(27, 12, 29, 1, 25.9, '[{"name":"Medium Rare","price":0},{"name":"Mit Zwiebeln","price":1.5}]'),
(28, 12, 31, 1, 21.4, '[]'),
(29, 12, 35, 1, 14.5, '[]'),
(30, 12, 2, 1, 3.1, '[]'),
(31, 12, 6, 1, 3.8, '[]'),
(32, 12, 16, 1, 3.8, '[]'),
(33, 12, 21, 2, 6.4, '[]'),
(34, 12, 24, 1, 5.2, '[]'),
(35, 12, 47, 1, 11.5, '[{"name":"Metaxa-Soße","price":0}]');

-- Option groups 
INSERT INTO option_groups (id, name, description, is_required, max_selections, min_selections, created_at) VALUES
(1, 'Gargrad', 'Wählen Sie den gewünschten Gargrad', 1, 1, 1, '2025-08-01 13:08:46'),
(2, 'Zwiebeln', 'Zwiebeln hinzufügen?', 0, 1, 0, '2025-08-01 13:08:46'),
(3, 'Soße', 'Wählen Sie eine Soße', 1, 1, 1, '2025-08-01 13:08:46'),
(4, 'Pizza-Belag', 'Zusätzlicher Belag für Pizza', 0, 5, 0, '2025-08-01 13:08:46');

-- Options
INSERT INTO options (id, option_group_id, name, description, price_modifier, is_default, created_at) VALUES
(1, 1, 'Rare', 'Sehr blutig - kurz angebraten', 0, 0, '2025-08-01 13:08:46'),
(2, 1, 'Medium Rare', 'Blutig - rosa Kern', 0, 1, '2025-08-01 13:08:46'),
(3, 1, 'Medium', 'Rosa - warmer roter Kern', 0, 0, '2025-08-01 13:08:46'),
(4, 1, 'Medium Well', 'Durch - leicht rosa', 0, 0, '2025-08-01 13:08:46'),
(5, 1, 'Well Done', 'Vollständig durchgebraten', 0, 0, '2025-08-01 13:08:46');

-- Grundeinstellungen
INSERT INTO settings (setting_key, setting_value, setting_type, description, category, is_editable, created_at, updated_at) VALUES
('opening_hours_monday', '{"open": "00:00", "close": "00:00", "closed": true}', 'json', 'Öffnungszeiten Montag', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('opening_hours_tuesday', '{"open": "17:30", "close": "23:00", "closed": false}', 'json', 'Öffnungszeiten Dienstag', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('opening_hours_wednesday', '{"open": "17:30", "close": "23:00", "closed": false}', 'json', 'Öffnungszeiten Mittwoch', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('opening_hours_thursday', '{"open": "17:30", "close": "23:00", "closed": false}', 'json', 'Öffnungszeiten Donnerstag', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('opening_hours_friday', '{"open": "17:30", "close": "23:00", "closed": false}', 'json', 'Öffnungszeiten Freitag', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('opening_hours_saturday', '{"open": "17:30", "close": "23:00", "closed": false}', 'json', 'Öffnungszeiten Samstag', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('opening_hours_sunday', '{"open": "11:30", "close": "22:00", "closed": false}', 'json', 'Öffnungszeiten Sonntag', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('restaurant_name', 'Restaurant Dionysos', 'string', 'Name des Restaurants', 'info', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('restaurant_phone', '06021 25779', 'string', 'Telefonnummer', 'info', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('restaurant_email', 'info@dionysos-aburg.de', 'string', 'E-Mail-Adresse', 'info', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('restaurant_address', 'Am Floßhafen 27, 63739 Aschaffenburg', 'string', 'Adresse', 'info', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_advance_days', '200', 'integer', 'Wie viele Tage im Voraus reserviert werden kann', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_min_duration', '120', 'integer', 'Mindestdauer einer Reservierung in Minuten', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_max_party_size', '20', 'integer', 'Maximale Personenanzahl pro Reservierung', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_time_slots_monday', '[]', 'json', 'Verfügbare Reservierungs-Zeitslots Montag', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_time_slots_tuesday', '["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]', 'json', 'Verfügbare Reservierungs-Zeitslots Dienstag', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_time_slots_wednesday', '["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]', 'json', 'Verfügbare Reservierungs-Zeitslots Mittwoch', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_time_slots_thursday', '["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]', 'json', 'Verfügbare Reservierungs-Zeitslots Donnerstag', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_time_slots_friday', '["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]', 'json', 'Verfügbare Reservierungs-Zeitslots Freitag', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_time_slots_saturday', '["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]', 'json', 'Verfügbare Reservierungs-Zeitslots Samstag', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('reservation_time_slots_sunday', '["11:30","12:00","12:30","13:00","13:30","14:00","14:30","15:00","15:30","16:00","16:30","17:00", "17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]', 'json', 'Verfügbare Reservierungs-Zeitslots Sonntag', 'reservation', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('telegram_bot_token', '**********:*************************', 'string', NULL, 'telegram', 1, '2025-08-03 11:24:14', '2025-08-03 11:24:14'),
('telegram_chat_id', '**************', 'string', NULL, 'telegram', 1, '2025-08-03 11:24:14', '2025-08-03 11:24:14'),
('admin_username', 'admin', 'string', NULL, 'auth', 1, '2025-08-03 12:32:51', '2025-08-03 12:32:51'),
('admin_password_hash', '*****************************************************', 'string', NULL, 'auth', 1, '2025-08-03 12:32:51', '2025-08-03 12:32:51'),
('reservation_system', '1', 'string', 'Reservierungssystem aktiviert/deaktiviert', 'general', 1, '2025-08-03 23:42:40', '2025-08-03 23:42:40'),
('order_system', '1', 'string', 'Bestellsystem aktiviert/deaktiviert', 'general', 1, '2025-08-03 23:42:40', '2025-08-03 23:42:40'),
('pickup_system', '1', 'string', 'Abholsystem aktiviert/deaktiviert', 'general', 1, '2025-08-03 23:42:40', '2025-08-03 23:42:40'),
('delivery_system', '0', 'string', 'Liefersystem aktiviert/deaktiviert', 'general', 1,'2025-08-03 23:42:40', '2025-08-03 23:42:40'),
('order_min_amount', '15.00', 'string', 'Mindestbestellwert', 'order', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('delivery_fee', '2.50', 'string', 'Liefergebühr', 'order', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('delivery_regions', '["63739","63741","63743"]', 'json', 'Lieferregion in PLZ', 'order', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('preparation_time', '30', 'integer', 'Zubereitungszeit in Minuten', 'order', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('special_days', '[]', 'json', 'Spezielle Öffnungszeiten oder Schließtage', 'hours', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37'),
('holiday_message', '', 'string', 'Nachricht für Feiertage', 'info', 1, '2025-08-02 14:27:37', '2025-08-02 14:27:37');

COMMIT;
