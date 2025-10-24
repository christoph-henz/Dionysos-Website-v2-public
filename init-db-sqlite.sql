BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS Gallery (
                         id INTEGER PRIMARY KEY AUTOINCREMENT,
                         image_id INT NOT NULL,
                         display_order INT DEFAULT 0,
                         active BOOLEAN DEFAULT TRUE,
                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, description VARCHAR(255),
                         FOREIGN KEY (image_id)
                             REFERENCES Images(id)
                             ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS Images (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        name VARCHAR(255) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS article (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plu TEXT NOT NULL UNIQUE,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    price REAL NOT NULL,
    description TEXT,
    FOREIGN KEY (category_id) REFERENCES article_category(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS article_category (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,     -- Enum-Name z. B. 'Grill'
    label TEXT NOT NULL            -- Anzeigename z. B. 'Spezialitäten vom Grill'
);
CREATE TABLE IF NOT EXISTS article_option_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            article_id INTEGER NOT NULL,
            option_group_id INTEGER NOT NULL,
            display_order INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (article_id) REFERENCES article(id) ON DELETE CASCADE,
            FOREIGN KEY (option_group_id) REFERENCES option_groups(id) ON DELETE CASCADE,
            UNIQUE(article_id, option_group_id)
        );
CREATE TABLE IF NOT EXISTS invoice (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    street TEXT NOT NULL,
    number TEXT NOT NULL,
    postal_code TEXT NOT NULL,
    city TEXT NOT NULL,
    email TEXT,
    phone TEXT,
    created_on TEXT NOT NULL,
    total_amount REAL DEFAULT 0,
    tax_amount REAL DEFAULT 0
, status TEXT DEFAULT 'pending', telegram_message_id INTEGER, notes TEXT);
CREATE TABLE IF NOT EXISTS option_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            is_required BOOLEAN DEFAULT FALSE,
            max_selections INTEGER DEFAULT 1,
            min_selections INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
CREATE TABLE IF NOT EXISTS options (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            option_group_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price_modifier DECIMAL(10,2) DEFAULT 0.00,
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (option_group_id) REFERENCES option_groups(id) ON DELETE CASCADE
        );
CREATE TABLE IF NOT EXISTS order_item (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL,
    article_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL,
    total_price REAL NOT NULL, options_json TEXT,
    FOREIGN KEY (invoice_id) REFERENCES invoice(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES article(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS order_item_options (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_item_id INTEGER NOT NULL,
            option_id INTEGER NOT NULL,
            quantity INTEGER DEFAULT 1,
            price_at_time DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (option_id) REFERENCES options(id) ON DELETE CASCADE
        );
CREATE TABLE IF NOT EXISTS reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    guests INTEGER NOT NULL CHECK (guests > 0),
    notes TEXT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
, telegram_message_id INTEGER);
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type TEXT DEFAULT 'string',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_editable BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS sqlite_stat4(tbl,idx,neq,nlt,ndlt,sample);
CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
INSERT INTO "Gallery" ("id","image_id","display_order","active","created_at","description") VALUES (1,1,0,1,'2025-07-29 14:30:00','Kamin');
INSERT INTO "Gallery" ("id","image_id","display_order","active","created_at","description") VALUES (2,2,1,1,'2025-07-29 14:37:07','Vorderer Gastraum');
INSERT INTO "Gallery" ("id","image_id","display_order","active","created_at","description") VALUES (3,3,2,1,'2025-07-29 14:37:20','Theke');
INSERT INTO "Images" ("id","name","created_at") VALUES (1,'04.jpg','2025-07-29 14:29:24');
INSERT INTO "Images" ("id","name","created_at") VALUES (2,'4.jpg','2025-07-29 14:36:02');
INSERT INTO "Images" ("id","name","created_at") VALUES (3,'1.jpg','2025-07-29 14:36:04');
INSERT INTO "Images" ("id","name","created_at") VALUES (4,'2.jpg','2025-07-29 14:36:05');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (1,'11',1,'Peperoni',3.1,'mit Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (2,'12',1,'Schwarze Oliven',3.1,'& Knoblauch aus Kalamata');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (3,'13',1,'Grüne Oliven',3.1,'gefüllt mit Paprika & Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (4,'14',1,'Original Griech. Schafskäse',3.8,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (5,'15',1,'Melitzanosalata',3.8,'Auberginenpaste & Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (6,'16',1,'Saziki',3.8,'Griech. Joghurt mit Gurken & Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (7,'17',1,'Tirokafteri',3.8,'Schafskäsepaste scharf & Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (8,'18',1,'Taramosalata',3.8,'Fischrogencreme');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (9,'19',1,'Sardellen',3.9,'eingelegt & gesalzen');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (10,'20',1,'Kalte Riesenbohnen',3.7,'mit Paprika & Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (11,'21',1,'Oktupussalat',4.2,'eingelegt in Öl-Essig');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (12,'52',1,'Weißbrot',1.9,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (13,'26',2,'Gegrillte Peperoni',4.2,'mit Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (14,'27',2,'Gegrillte Aubergine',3.8,'natur mit Olivenöl');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (15,'28',2,'Gegrillte Zucchini',3.8,'natur mit Olivenöl');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (16,'29',2,'Gegrillte Champignons',3.8,'natur mit Olivenöl');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (17,'30',2,'Dolmadakia',3.8,'Weinblätter mit Reisfüllung');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (18,'31',2,'Bruschetta',3.9,'mit Tomaten-Knoblauch Aufstrich');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (19,'32',2,'Knoblauchbrot',3.9,'mit mediterranen Gewürzen');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (20,'33',2,'Saganaki',4.2,'panierter Mischkäse');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (21,'34',2,'Pitta',3.2,'gegrilltes Fladenbrot');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (22,'22',3,'Gemischter Beilagensalat',4.2,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (23,'23',3,'Krautsalat eingelegt',3.9,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (24,'24',3,'Kl. Bauernsalat',5.2,'Tomate, Gurke, Schafskäse, Zwiebeln');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (25,'45',6,'Gr. Bauernsalat',11.4,'Tomaten, Gurken, Zwiebeln, Schafskäse & Saziki');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (26,'46',6,'Großer gemischter Salatteller',14.4,'mit gegrillter Hähnchenbrust');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (27,'48',6,'Großer gemischter Salatteller',14.2,'mit Thunfisch und Ei');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (28,'49',6,'Großer gemischter Salatteller',13.4,'mit gebratenen Champignons');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (29,'50',7,'Argentinisches Rumpsteak',24.4,'(ca. 220 g Rohgewicht) gegrillt mit Kräuterbutter dazu Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (30,'51',7,'Rinderleber',14.2,'gebraten mit Zwiebeln & Butter-Reis');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (31,'54',7,'Lammspieß',21.4,'gegrillter Lammrücken mit Knoblauch, Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (32,'55',7,'Paidakia',21.4,'gegrillte Lammkoteletts mit Knoblauch, Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (33,'56',7,'Lammteller',21.6,'gegrillte Lammkoteletts & Lammrücken mit Knoblauch, Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (34,'57',7,'Lammhaxe',19.2,'aus dem Ofen mit Stifado-Zwiebeln & Brot');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (35,'65',7,'Gyrosteller',14.5,'vom Schwein mit Bohnen, Steakhouse-Pommes & Saziki');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (36,'66',8,'Gyros Metaxa',15.5,'vom Schwein in Metaxasoße & Pilzen, aus dem Ofen mit Käse überbacken & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (37,'67',8,'Riganato',15.8,'Schweinesteak in Metaxasoße & Pilzen, aus dem Ofen mit Käse überbacken & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (38,'69',7,'Bifteki',15.6,'gegrilltes Schweinehacksteak gefüllt mit Schafskäse dazu Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (39,'71',7,'Suwlakia',14.6,'gegrillte Schweinespieße dazu Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (40,'72',7,'Gegrillter Bauernspieß',17.5,'mageres Schweinefleisch dazu Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (41,'73',7,'Hähnchenspieße',15.6,'mit Bohnen & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (42,'40',5,'Saganaki Natur',10.9,'Mischkäse im Steintopf dazu Tomate, Peperoni & Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (43,'41',5,'Saganaki Paniert',11.2,'Mischkäse im Steintopf dazu Peperoni & Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (44,'42',5,'Pikilia',12.5,'panierte Aubergine & Zucchini dazu Peperoni, Oliven, Saganaki & Saziki');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (45,'43',5,'Gigantes',10.9,'Riesenbohnen im Steintopf mit Paprika, Schafskäse und Knoblauch');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (47,'44',5,'Juwetzi',11.5,'griechische Nudeln mit Metaxa -oder Tomatensoße, Schafskäse und Edamer Käse überbacken');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (48,'59',10,'Naki Teller',17.8,'Gyros, Suwlaki, Suzukaki dazu Bohnen, Saziki & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (49,'60',10,'Vinka Teller',19.8,'Gyros, Suwlaki, Suzukaki, Hänchenspieß dazu Bohnen, Saziki & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (50,'61',10,'Athina Teller',19.8,'Gyros, Suwlaki, Suzukaki, Leber dazu Bohnen, Saziki & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (51,'62',10,'Dionys Teller',21.6,'Gyros, Suwlaki, Bifteki, Lammkotelett dazu Bohnen, Saziki & Steakhouse-Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (52,'74',11,'Gavros',14.2,'in Maismehl panierte Sardellen dazu Saziki und Brot');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (53,'75',11,'Panierte Babycalamari',18.4,'mit Knoblauchöl dazu Butter-Reis');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (54,'76',11,'Gegrilltes Lachsfilet',18.8,'mit Knoblauchöl dazu Butter-Reis');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (55,'1',13,'Pizza Margarita',8.3,'Tomatensoße, Käse');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (56,'2',13,'Pizza Salami',8.6,'Tomatensoße, Käse, Salami');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (57,'3',13,'Pizza Spezial',10.4,'Tomatensoße, Käse, Salami, Schinken, Paprika, Zwiebeln, Pilze');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (58,'360',14,'Hähnchenspieß',8.2,'gegrillt mit Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (59,'361',14,'Schweinespieß',7.9,'gegrillt mit Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (60,'362',14,'Gyros vom Drehspieß',8.7,'mit Pommes und Saziki');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (61,'363',14,'Suzukaki',7.2,'ungefülltes Hacksteak mit Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (62,'364',14,'Bifteki',8.5,'gefülltes Hacksteak mit Schafskäse dazu Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (63,'365',14,'Schnitzel',8.2,'paniert dazu Pommes');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (64,'4',13,'Pizza Schinken Schafskäse',11.3,'Tomatensoße, Käse, Schafskäse, Schinken, Zwiebeln');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (65,'5',13,'Pizza Thunfisch',11.4,'Tomatensoße, Käse, Thunfisch, Artischocken, Zwiebeln, Kapern');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (66,'6',13,'Pizza Gyros',12.4,'Tomatensoße, Käse, Gyros, Schafskäse, Zwiebeln');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (67,'7',13,'Pizza Veggie',10.4,'Tomatensoße, Käse, Pilze, Paprika, Zwiebeln, Mais & Tomaten');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (68,'35',4,'Nudel',3.7,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (69,'36',4,'Butterreis',3.7,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (70,'37',4,'Portion Grüne Bohnen',3.7,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (71,'38',4,'Portion Steakhouse – Pommes',4.2,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (72,'39',4,'Portion Ajvar (Paprika Dip)',2.4,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (73,'53',4,'Extra Metaxasoße',3.9,NULL);
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (74,'81',12,'Joghurt',4.9,'mit Honig und Walnüssen');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (75,'82',12,'Galaktobureko',6.8,'warmer Grießauflauf im Blätterteig');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (76,'83',12,'Souffle',5.9,'mit flüssigem Kern');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (77,'506',15,'Akropolis Platte (für 2 Personen)',41.8,'2x Suwlaki, 2x Lammkoteletts, 2x Schweinesteaks und Gyros, dazu Pommes, grüne Bohnen und Saziki');
INSERT INTO "article" ("id","plu","category_id","name","price","description") VALUES (78,'507',15,'Poseidon Platte (für 2 Personen)',48.9,'2x Lachs, 2x Garnelenspieß und Calamari, dazu Reis und Beilagensalat');
INSERT INTO "article_category" ("id","code","label") VALUES (1,'Meze_Cold','Meze Kalt');
INSERT INTO "article_category" ("id","code","label") VALUES (2,'Meze_Warm','Meze Warm');
INSERT INTO "article_category" ("id","code","label") VALUES (3,'Supp_Salad','Beilagensalate');
INSERT INTO "article_category" ("id","code","label") VALUES (4,'Supplements','Beilagen');
INSERT INTO "article_category" ("id","code","label") VALUES (5,'Vegetarian','Vegetarische Hauptspeisen');
INSERT INTO "article_category" ("id","code","label") VALUES (6,'Salads','Salatteller ');
INSERT INTO "article_category" ("id","code","label") VALUES (7,'Grill','Spezialitäten vom Grill');
INSERT INTO "article_category" ("id","code","label") VALUES (8,'Oven','Aus dem Ofen');
INSERT INTO "article_category" ("id","code","label") VALUES (9,'Skewers','Fleischspieße');
INSERT INTO "article_category" ("id","code","label") VALUES (10,'Plates','Unsere Grillteller');
INSERT INTO "article_category" ("id","code","label") VALUES (11,'Fish','Fischspezialitäten');
INSERT INTO "article_category" ("id","code","label") VALUES (12,'Dessert','Nachspeisen');
INSERT INTO "article_category" ("id","code","label") VALUES (13,'Pizza','Pizza');
INSERT INTO "article_category" ("id","code","label") VALUES (14,'Childs_Meal','Kinderkarte');
INSERT INTO "article_category" ("id","code","label") VALUES (15,'Special_Menu','Wochenkarte');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (1,29,1,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (2,29,2,2,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (3,47,3,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (4,55,4,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (5,56,4,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (6,57,4,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (7,64,4,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (8,65,4,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (9,66,4,1,'2025-08-01 13:10:50');
INSERT INTO "article_option_groups" ("id","article_id","option_group_id","display_order","created_at") VALUES (10,67,4,1,'2025-08-01 13:10:50');
INSERT INTO "option_groups" ("id","name","description","is_required","max_selections","min_selections","created_at") VALUES (1,'Gargrad','Wählen Sie den gewünschten Gargrad',1,1,1,'2025-08-01 13:08:46');
INSERT INTO "option_groups" ("id","name","description","is_required","max_selections","min_selections","created_at") VALUES (2,'Zwiebeln','Zwiebeln hinzufügen?',0,1,0,'2025-08-01 13:08:46');
INSERT INTO "option_groups" ("id","name","description","is_required","max_selections","min_selections","created_at") VALUES (3,'Soße','Wählen Sie eine Soße',1,1,1,'2025-08-01 13:08:46');
INSERT INTO "option_groups" ("id","name","description","is_required","max_selections","min_selections","created_at") VALUES (4,'Pizza-Belag','Zusätzlicher Belag für Pizza',0,5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (1,1,'Rare','Sehr blutig - kurz angebraten',0,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (2,1,'Medium Rare','Blutig - rosa Kern',0,1,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (3,1,'Medium','Rosa - warmer roter Kern',0,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (4,1,'Medium Well','Durch - leicht rosa',0,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (5,1,'Well Done','Vollständig durchgebraten',0,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (6,2,'Mit Zwiebeln','Gebratene Zwiebeln',1.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (7,2,'Ohne Zwiebeln','Keine Zwiebeln',0,1,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (8,3,'Metaxa-Soße','Hausgemachte Metaxa-Soße',0,1,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (9,3,'Tomatensoße','Tomatensoße',0,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (10,4,'Extra Käse','Zusätzlicher Käse',2,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (11,4,'Salami','Italienische Salami',2.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (12,4,'Schinken','Gekochter Schinken',2.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (13,4,'Thunfisch','Extra Thunfisch',2.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (14,4,'Kapern','Kapern',1.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (15,4,'Peperoni','Scharfe Peperoni',1.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (16,4,'Champignons','Frische Champignons',1.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (17,4,'Oliven','Schwarze Oliven',1.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (18,4,'Paprika','Bunte Paprika',1.5,0,'2025-08-01 13:08:46');
INSERT INTO "options" ("id","option_group_id","name","description","price_modifier","is_default","created_at") VALUES (19,4,'Zwiebeln','Rote Zwiebeln',1,0,'2025-08-01 13:08:46');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (22,10,2,1,3.1,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (23,10,29,1,25.9,'[{"name":"Rare","price":0},{"name":"Mit Zwiebeln","price":1.5}]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (24,11,2,1,3.1,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (25,11,29,1,25.9,'[{"name":"Medium Rare","price":0},{"name":"Mit Zwiebeln","price":1.5}]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (26,11,64,1,17.8,'[{"name":"Kapern","price":1.5},{"name":"Paprika","price":1.5},{"name":"Thunfisch","price":2.5},{"name":"Zwiebeln","price":1}]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (27,12,29,1,25.9,'[{"name":"Medium Rare","price":0},{"name":"Mit Zwiebeln","price":1.5}]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (28,12,31,1,21.4,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (29,12,35,1,14.5,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (30,12,2,1,3.1,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (31,12,6,1,3.8,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (32,12,16,1,3.8,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (33,12,21,2,6.4,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (34,12,24,1,5.2,'[]');
INSERT INTO "order_item" ("id","invoice_id","article_id","quantity","total_price","options_json") VALUES (35,12,47,1,11.5,'[{"name":"Metaxa-So\u00dfe","price":0}]');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (1,'opening_hours_monday','{"open": "00:00", "close": "00:00", "closed": true}','json','Öffnungszeiten Montag','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (2,'opening_hours_tuesday','{"open": "17:30", "close": "23:00", "closed": false}','json','Öffnungszeiten Dienstag','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (3,'opening_hours_wednesday','{"open": "17:30", "close": "23:00", "closed": false}','json','Öffnungszeiten Mittwoch','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (4,'opening_hours_thursday','{"open": "17:30", "close": "23:00", "closed": false}','json','Öffnungszeiten Donnerstag','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (5,'opening_hours_friday','{"open": "17:30", "close": "23:00", "closed": false}','json','Öffnungszeiten Freitag','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (6,'opening_hours_saturday','{"open": "17:30", "close": "23:00", "closed": false}','json','Öffnungszeiten Samstag','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (7,'opening_hours_sunday','{"open": "11:30", "close": "22:00", "closed": false}','json','Öffnungszeiten Sonntag','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (12,'restaurant_name','Restaurant Dionysos','string','Name des Restaurants','info',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (13,'restaurant_phone','06021 25779','string','Telefonnummer','info',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (14,'restaurant_email','info@dionysos-aburg.de','string','E-Mail-Adresse','info',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (15,'restaurant_address','Am Floßhafen 27, 63739 Aschaffenburg','string','Adresse','info',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (16,'reservation_advance_days','200','integer','Wie viele Tage im Voraus reserviert werden kann','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (17,'reservation_min_duration','120','integer','Mindestdauer einer Reservierung in Minuten','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (18,'reservation_max_party_size','20','integer','Maximale Personenanzahl pro Reservierung','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (19,'reservation_time_slots_monday','[]','json','Verfügbare Reservierungs-Zeitslots Montag','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (20,'reservation_time_slots_tuesday','["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]','json','Verfügbare Reservierungs-Zeitslots Dienstag','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (21,'reservation_time_slots_wednesday','["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]','json','Verfügbare Reservierungs-Zeitslots Mittwoch','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (22,'reservation_time_slots_thursday','["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]','json','Verfügbare Reservierungs-Zeitslots Donnerstag','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (23,'reservation_time_slots_friday','["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]','json','Verfügbare Reservierungs-Zeitslots Freitag','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (24,'reservation_time_slots_saturday','["17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]','json','Verfügbare Reservierungs-Zeitslots Samstag','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (25,'reservation_time_slots_sunday','["11:30","12:00","12:30","13:00","13:30","14:00","14:30","15:00","15:30","16:00","16:30","17:00", "17:30", "18:00", "18:30", "19:00", "19:30", "20:00", "20:30"]','json','Verfügbare Reservierungs-Zeitslots Sonntag','reservation',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (26,'order_min_amount','15.00','string','Mindestbestellwert','order',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (27,'delivery_fee','2.50','string','Liefergebühr','order',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (28,'delivery_regions','["63739","63741","63743"]','json','Lieferregion in PLZ','order',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (29,'preparation_time','30','integer','Zubereitungszeit in Minuten','order',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (30,'special_days','[]','json','Spezielle Öffnungszeiten oder Schließtage','hours',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (31,'holiday_message','','string','Nachricht für Feiertage','info',1,'2025-08-02 14:27:37','2025-08-02 14:27:37');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (38,'telegram_bot_token','***********:********************************','string',NULL,'telegram',1,'2025-08-03 11:24:14','2025-08-03 11:24:14');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (39,'telegram_chat_id','*************','string',NULL,'telegram',1,'2025-08-03 11:24:14','2025-08-03 11:24:14');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (42,'admin_username','admin','string',NULL,'auth',1,'2025-08-03 12:32:51','2025-08-03 12:32:51');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (43,'admin_password_hash','*****************************************','string',NULL,'auth',1,'2025-08-03 12:32:51','2025-08-03 12:32:51');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (44,'reservation_system','1','string','Reserviersystem aktiviert','general',1,'2025-08-03 23:06:48','2025-08-03 23:06:48');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (45,'order_system','1','string','Bestellsystem aktiviert','general',1,'2025-08-03 23:06:48','2025-08-03 23:06:48');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (46,'pickup_system','1','string','Abholsystem aktiviert','general',1,'2025-08-03 23:06:48','2025-08-03 23:06:48');
INSERT INTO "settings" ("id","setting_key","setting_value","setting_type","description","category","is_editable","created_at","updated_at") VALUES (47,'delivery_system','0','string','Liefersystem aktiviert','general',1,'2025-08-03 23:06:48','2025-08-03 23:06:48');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','2 1','0 0','0 0',X'031501617574682a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','4 1','2 3','1 3',X'031b0167656e6572616c2d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','8 1','6 7','2 7',X'031701686f75727302');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','8 1','6 11','2 11',X'031701686f75727306');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','5 1','14 15','3 15',X'031501696e666f0d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','4 1','19 19','4 19',X'0317016f726465721a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','10 1','23 23','5 23',X'0323017265736572766174696f6e10');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','10 1','23 27','5 27',X'0323017265736572766174696f6e14');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','10 1','23 31','5 31',X'0323017265736572766174696f6e18');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_category','2 1','33 34','6 34',X'031d0174656c656772616d27');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','3 3','3 3',X'032d0164656c69766572795f726567696f6e731c');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','4 4','4 4',X'032b0164656c69766572795f73797374656d2f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','5 5','5 5',X'032b01686f6c696461795f6d6573736167651f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','6 6','6 6',X'0335016f70656e696e675f686f7572735f66726964617905');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','7 7','7 7',X'0335096f70656e696e675f686f7572735f6d6f6e646179');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','9 9','9 9',X'0335016f70656e696e675f686f7572735f73756e64617907');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','11 11','11 11',X'0337016f70656e696e675f686f7572735f7475657364617902');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','13 13','13 13',X'032d016f726465725f6d696e5f616d6f756e741a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','14 14','14 14',X'0325016f726465725f73797374656d2d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','15 15','15 15',X'0327017069636b75705f73797374656d2e');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','17 17','17 17',X'033d017265736572766174696f6e5f616476616e63655f6461797310');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','18 18','18 18',X'0341017265736572766174696f6e5f6d61785f70617274795f73697a6512');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','19 19','19 19',X'033d017265736572766174696f6e5f6d696e5f6475726174696f6e11');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','21 21','21 21',X'0347017265736572766174696f6e5f74696d655f736c6f74735f66726964617917');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','22 22','22 22',X'0347017265736572766174696f6e5f74696d655f736c6f74735f6d6f6e64617913');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','23 23','23 23',X'034b017265736572766174696f6e5f74696d655f736c6f74735f736174757264617918');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','24 24','24 24',X'0347017265736572766174696f6e5f74696d655f736c6f74735f73756e64617919');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','25 25','25 25',X'034b017265736572766174696f6e5f74696d655f736c6f74735f746875727364617916');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','26 26','26 26',X'0349017265736572766174696f6e5f74696d655f736c6f74735f7475657364617914');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','27 27','27 27',X'034d017265736572766174696f6e5f74696d655f736c6f74735f7765646e657364617915');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','28 28','28 28',X'03310172657374617572616e745f616464726573730f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','29 29','29 29',X'032d0172657374617572616e745f656d61696c0e');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','30 30','30 30',X'032b0172657374617572616e745f6e616d650c');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','idx_setting_key','1 1','31 31','31 31',X'032d0172657374617572616e745f70686f6e650d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','3 3','3 3',X'032d0164656c69766572795f726567696f6e731c');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','4 4','4 4',X'032b0164656c69766572795f73797374656d2f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','5 5','5 5',X'032b01686f6c696461795f6d6573736167651f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','6 6','6 6',X'0335016f70656e696e675f686f7572735f66726964617905');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','7 7','7 7',X'0335096f70656e696e675f686f7572735f6d6f6e646179');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','9 9','9 9',X'0335016f70656e696e675f686f7572735f73756e64617907');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','11 11','11 11',X'0337016f70656e696e675f686f7572735f7475657364617902');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','13 13','13 13',X'032d016f726465725f6d696e5f616d6f756e741a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','14 14','14 14',X'0325016f726465725f73797374656d2d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','15 15','15 15',X'0327017069636b75705f73797374656d2e');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','17 17','17 17',X'033d017265736572766174696f6e5f616476616e63655f6461797310');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','18 18','18 18',X'0341017265736572766174696f6e5f6d61785f70617274795f73697a6512');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','19 19','19 19',X'033d017265736572766174696f6e5f6d696e5f6475726174696f6e11');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','21 21','21 21',X'0347017265736572766174696f6e5f74696d655f736c6f74735f66726964617917');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','22 22','22 22',X'0347017265736572766174696f6e5f74696d655f736c6f74735f6d6f6e64617913');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','23 23','23 23',X'034b017265736572766174696f6e5f74696d655f736c6f74735f736174757264617918');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','24 24','24 24',X'0347017265736572766174696f6e5f74696d655f736c6f74735f73756e64617919');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','25 25','25 25',X'034b017265736572766174696f6e5f74696d655f736c6f74735f746875727364617916');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','26 26','26 26',X'0349017265736572766174696f6e5f74696d655f736c6f74735f7475657364617914');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','27 27','27 27',X'034d017265736572766174696f6e5f74696d655f736c6f74735f7765646e657364617915');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','28 28','28 28',X'03310172657374617572616e745f616464726573730f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','29 29','29 29',X'032d0172657374617572616e745f656d61696c0e');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','30 30','30 30',X'032b0172657374617572616e745f6e616d650c');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('settings','sqlite_autoindex_settings_1','1 1','31 31','31 31',X'032d0172657374617572616e745f70686f6e650d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','2 1 1','0 0 0','0 0 0',X'040109091d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','2 1 1','0 1 1','0 1 1',X'040101011d0202');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','2 2 2','1 2 2',X'040101012f0303');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','3 3 3','2 3 3',X'04010101370404');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','4 4 4','3 4 4',X'04010101380405');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','5 5 5','4 5 5',X'04010101390406');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','6 6 6','5 6 6',X'04010101400407');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','7 7 7','6 7 7',X'04010101410408');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','8 8 8','7 8 8',X'04010101420409');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_option_groups','sqlite_autoindex_article_option_groups_1','1 1 1','9 9 9','8 9 9',X'0401010143040a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('system_settings','sqlite_autoindex_system_settings_1','1 1','0 0','0 0',X'032b0164656c69766572795f73797374656d04');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('system_settings','sqlite_autoindex_system_settings_1','1 1','1 1','1 1',X'0325016f726465725f73797374656d02');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('system_settings','sqlite_autoindex_system_settings_1','1 1','2 2','2 2',X'0327017069636b75705f73797374656d03');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('system_settings','sqlite_autoindex_system_settings_1','1 1','3 3','3 3',X'0331097265736572766174696f6e5f73797374656d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_status','1 1','0 0','0 0',X'031b016172726976656404');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_status','3 1','1 1','1 1',X'031f01636f6e6669726d656403');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_status','3 1','1 3','1 3',X'031f01636f6e6669726d656407');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_status','1 1','4 4','2 4',X'031b016e6f5f73686f7708');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_status','3 1','5 5','3 5',X'031b0970656e64696e67');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_status','3 1','5 7','3 7',X'031b0170656e64696e6705');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_status','1 1','8 8','4 8',X'031d0172656a656374656409');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_date','1 1','0 0','0 0',X'032101323032352d30382d303302');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_date','6 1','1 1','1 1',X'032101323032352d30382d303404');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_date','6 1','1 3','1 3',X'032101323032352d30382d303406');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_date','6 1','1 5','1 5',X'032101323032352d30382d303408');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('reservations','idx_reservation_date','2 1','7 7','2 7',X'032109323032352d30382d3036');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','7 7','7 7',X'031101313707');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','9 9','9 9',X'031101313909');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','16 16','16 16',X'03110132360d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','18 18','18 18',X'03110132380f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','19 19','19 19',X'031101323910');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','20 20','20 20',X'030f013339');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','23 23','23 23',X'031101333213');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','28 28','28 28',X'0313013336303a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','29 29','29 29',X'0313013336313b');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','39 39','39 39',X'03110134312b');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','48 48','48 48',X'03110135301d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','49 49','49 49',X'0313013530304d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','51 51','51 51',X'0313013530324f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','52 52','52 52',X'03130135303350');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','57 57','57 57',X'031101353349');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','59 59','59 59',X'031101353520');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','64 64','64 64',X'031101363031');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','69 69','69 69',X'031101363725');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','71 71','71 71',X'030f013743');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','72 72','72 72',X'031101373127');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','76 76','76 76',X'031101373535');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','77 77','77 77',X'031101373636');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','78 78','78 78',X'03110138314a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article','sqlite_autoindex_article_1','1 1','79 79','79 79',X'03110138324b');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','0 0','0 0',X'0323014368696c64735f4d65616c0e');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','1 1','1 1',X'031b01446573736572740c');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','2 2','2 2',X'031501466973680b');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','3 3','3 3',X'0317014772696c6c07');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','4 4','4 4',X'031f094d657a655f436f6c64');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','5 5','5 5',X'031f014d657a655f5761726d02');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','6 6','6 6',X'0315014f76656e08');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','7 7','7 7',X'03170150697a7a610d');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','8 8','8 8',X'031901506c617465730a');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','9 9','9 9',X'03190153616c61647306');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','10 10','10 10',X'031b01536b657765727309');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','11 11','11 11',X'0325015370656369616c5f4d656e750f');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','12 12','12 12',X'032101537570705f53616c616403');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','13 13','13 13',X'032301537570706c656d656e747304');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('article_category','sqlite_autoindex_article_category_1','1 1','14 14','14 14',X'0321015665676574617269616e05');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('Gallery','idx_gallery_order','1 1','0 0','0 0',X'030809');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('Gallery','idx_gallery_order','1 1','1 1','1 1',X'03090102');
INSERT INTO "sqlite_stat4" ("tbl","idx","neq","nlt","ndlt","sample") VALUES ('Gallery','idx_gallery_order','1 1','2 2','2 2',X'0301010203');
INSERT INTO "system_settings" ("id","setting_key","setting_value","description","created_at","updated_at") VALUES (1,'reservation_system','1','Reservierungssystem aktiviert/deaktiviert','2025-08-03 23:42:40','2025-08-03 23:42:40');
INSERT INTO "system_settings" ("id","setting_key","setting_value","description","created_at","updated_at") VALUES (2,'order_system','1','Bestellsystem aktiviert/deaktiviert','2025-08-03 23:42:40','2025-08-03 23:42:40');
INSERT INTO "system_settings" ("id","setting_key","setting_value","description","created_at","updated_at") VALUES (3,'pickup_system','1','Abholsystem aktiviert/deaktiviert','2025-08-03 23:42:40','2025-08-03 23:42:40');
INSERT INTO "system_settings" ("id","setting_key","setting_value","description","created_at","updated_at") VALUES (4,'delivery_system','1','Liefersystem aktiviert/deaktiviert','2025-08-03 23:42:40','2025-08-03 23:42:40');
CREATE INDEX idx_gallery_order ON Gallery(display_order);
CREATE INDEX idx_reservation_date ON reservations(reservation_date);
CREATE INDEX idx_reservation_status ON reservations(status);
CREATE INDEX idx_setting_category ON settings(category);
CREATE INDEX idx_setting_key ON settings(setting_key);
COMMIT;
