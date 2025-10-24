<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// CORS-Header für AJAX-Requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Für OPTIONS-Request (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Nur POST-Requests verarbeiten
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Datenbankverbindung - gleiche Logik wie andere APIs
    $isLocal = $_SERVER['HTTP_HOST'] === 'localhost';
    
    if ($isLocal) {
        // SQLite für lokale Entwicklung
        $pdo = new PDO('sqlite:' . __DIR__ . '/../../database.db');
    } else {
        // MySQL für Produktion
        $pdo = new PDO(
            'mysql:host=db************.hosting-data.io;dbname=dbs**********;charset=utf8mb4',
            'dbu***********', 
            '*************************'
        );
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Request-Body parsen
    if (isset($_POST['action'])) {
        // Form-Data für File-Upload
        $input = $_POST;
    } else {
        // JSON für andere Aktionen
        $input = json_decode(file_get_contents('php://input'), true);
    }
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    $action = $input['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Missing action parameter');
    }
    
    switch ($action) {
        case 'update_system_setting':
            handleSystemSettingUpdate($pdo, $input);
            break;
            
        case 'upload_image':
            handleImageUpload($pdo, $isLocal);
            break;
            
        case 'delete_image':
            handleImageDelete($pdo, $input);
            break;
            
        case 'add_to_gallery':
            handleAddToGallery($pdo, $input, $isLocal);
            break;
            
        case 'remove_from_gallery':
            handleRemoveFromGallery($pdo, $input);
            break;
            
        case 'addToGallery':
            handleAddToGallery($pdo, $input, $isLocal);
            break;
            
        case 'removeFromGallery':
            handleRemoveFromGallery($pdo, $input);
            break;
            
        case 'updateGalleryOrder':
            handleUpdateGalleryOrder($pdo, $input);
            break;
            
        case 'updateGalleryDescription':
            handleUpdateGalleryDescription($pdo, $input);
            break;
            
        case 'updateSystemSetting':
            handleSystemSettingUpdate($pdo, $input);
            break;
            
        case 'deleteImage':
            handleImageDelete($pdo, $input);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleSystemSettingUpdate($pdo, $input) {
    $settingKey = $input['setting_key'] ?? null;
    $settingValue = $input['setting_value'] ?? null;
    
    if (!$settingKey || $settingValue === null) {
        throw new Exception('Missing setting_key or setting_value');
    }
    
    // Validiere erlaubte Settings
    $allowedSettings = ['order_system', 'reservation_system', 'pickup_system', 'delivery_system'];
    if (!in_array($settingKey, $allowedSettings)) {
        throw new Exception('Setting not allowed: ' . $settingKey);
    }
    
    // Validiere Wert (nur 0 oder 1)
    if (!in_array($settingValue, ['0', '1'])) {
        throw new Exception('Invalid setting value. Only 0 or 1 allowed.');
    }
    
    // Update oder Insert Setting
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value, setting_type, category, created_at, updated_at) 
        VALUES (?, ?, 'string', 'general', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value), 
        updated_at = CURRENT_TIMESTAMP
    ");
    
    // Für SQLite verwenden wir REPLACE
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $stmt = $pdo->prepare("
            REPLACE INTO settings (setting_key, setting_value, setting_type, category, created_at, updated_at) 
            VALUES (?, ?, 'string', 'general', datetime('now'), datetime('now'))
        ");
    }
    
    $success = $stmt->execute([$settingKey, $settingValue]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => "Einstellung '{$settingKey}' erfolgreich aktualisiert"
        ]);
    } else {
        throw new Exception('Fehler beim Aktualisieren der Einstellung');
    }
}

function handleImageUpload($pdo, $isLocal) {
    if (!isset($_FILES['image'])) {
        throw new Exception('Keine Datei hochgeladen');
    }
    
    $file = $_FILES['image'];
    
    // Validierung
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fehler beim Hochladen: ' . $file['error']);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Dateityp nicht erlaubt. Nur JPG, PNG und WEBP sind erlaubt.');
    }
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('Datei zu groß. Maximum: 5MB');
    }
    
    // Eindeutigen Dateinamen generieren
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $basename = pathinfo($file['name'], PATHINFO_FILENAME);
    $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
    
    $counter = 0;
    $fileName = $basename . '.' . $extension;
    $targetPath = __DIR__ . '/../../public/assets/img/' . $fileName;
    
    // Prüfe ob Dateiname bereits existiert
    while (file_exists($targetPath)) {
        $counter++;
        $fileName = $basename . '_' . $counter . '.' . $extension;
        $targetPath = __DIR__ . '/../../public/assets/img/' . $fileName;
    }
    
    // Datei speichern
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Fehler beim Speichern der Datei');
    }
    
    // In Datenbank eintragen
    if ($isLocal) {
        $stmt = $pdo->prepare("INSERT INTO images (name, created_at) VALUES (?, datetime('now'))");
    } else {
        $stmt = $pdo->prepare("INSERT INTO images (name, created_at) VALUES (?, NOW())");
    }
    
    $success = $stmt->execute([$fileName]);
    
    if ($success) {
        $imageId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Bild erfolgreich hochgeladen',
            'image_id' => $imageId,
            'filename' => $fileName
        ]);
    } else {
        // Datei wieder löschen wenn DB-Eintrag fehlschlägt
        unlink($targetPath);
        throw new Exception('Fehler beim Speichern in der Datenbank');
    }
}

function handleImageDelete($pdo, $input) {
    $imageId = $input['image_id'] ?? null;
    
    if (!$imageId) {
        throw new Exception('Missing image_id');
    }
    
    // Bild-Informationen laden
    $stmt = $pdo->prepare("SELECT name FROM images WHERE id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$image) {
        throw new Exception('Bild nicht gefunden');
    }
    
    // Prüfe ob Bild in Galerie verwendet wird
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gallery WHERE image_id = ?");
    $stmt->execute([$imageId]);
    $inGallery = $stmt->fetchColumn() > 0;
    
    // Beginne Transaktion
    $pdo->beginTransaction();
    
    try {
        // Entferne aus Galerie falls vorhanden
        if ($inGallery) {
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE image_id = ?");
            $stmt->execute([$imageId]);
        }
        
        // Entferne aus images Tabelle
        $stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
        $stmt->execute([$imageId]);
        
        // Datei löschen
        $filePath = __DIR__ . '/../../public/assets/img/' . $image['name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Bild erfolgreich gelöscht'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleAddToGallery($pdo, $input, $isLocal) {
    $imageId = $input['image_id'] ?? null;
    $description = $input['description'] ?? '';
    
    if (!$imageId) {
        throw new Exception('Missing image_id');
    }
    
    // Prüfe ob Bild existiert
    $stmt = $pdo->prepare("SELECT name FROM images WHERE id = ?");
    $stmt->execute([$imageId]);
    if (!$stmt->fetch()) {
        throw new Exception('Bild nicht gefunden');
    }
    
    // Prüfe ob bereits in Galerie
    $stmt = $pdo->prepare("SELECT id FROM gallery WHERE image_id = ?");
    $stmt->execute([$imageId]);
    if ($stmt->fetch()) {
        throw new Exception('Bild ist bereits in der Galerie');
    }
    
    // Ermittle nächste display_order
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(display_order), -1) + 1 FROM gallery");
    $stmt->execute();
    $displayOrder = $stmt->fetchColumn();
    
    // Füge zur Galerie hinzu
    if ($isLocal) {
        $stmt = $pdo->prepare("
            INSERT INTO gallery (image_id, display_order, active, description, created_at) 
            VALUES (?, ?, 1, ?, datetime('now'))
        ");
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO gallery (image_id, display_order, active, description, created_at) 
            VALUES (?, ?, 1, ?, NOW())
        ");
    }
    
    $success = $stmt->execute([$imageId, $displayOrder, $description]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Bild erfolgreich zur Galerie hinzugefügt'
        ]);
    } else {
        throw new Exception('Fehler beim Hinzufügen zur Galerie');
    }
}

function handleRemoveFromGallery($pdo, $input) {
    // Unterstütze beide Parameter-Namen für Kompatibilität
    $imageId = $input['image_id'] ?? null;
    $galleryId = $input['gallery_id'] ?? null;
    
    if ($galleryId) {
        // Entferne anhand der gallery_id
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $success = $stmt->execute([$galleryId]);
    } elseif ($imageId) {
        // Entferne anhand der image_id
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE image_id = ?");
        $success = $stmt->execute([$imageId]);
    } else {
        throw new Exception('Missing image_id or gallery_id');
    }
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Bild erfolgreich aus der Galerie entfernt'
        ]);
    } else {
        throw new Exception('Bild nicht in der Galerie gefunden');
    }
}

function handleUpdateGalleryOrder($pdo, $input) {
    $orderData = $input['orderData'] ?? null;
    
    if (!$orderData || !is_array($orderData)) {
        throw new Exception('Missing or invalid orderData');
    }
    
    try {
        $pdo->beginTransaction();
        
        foreach ($orderData as $item) {
            $galleryId = $item['gallery_id'] ?? null;
            $displayOrder = $item['display_order'] ?? null;
            
            if (!$galleryId || !$displayOrder) {
                throw new Exception('Invalid orderData item');
            }
            
            $stmt = $pdo->prepare("UPDATE gallery SET display_order = ? WHERE id = ?");
            $stmt->execute([$displayOrder, $galleryId]);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Reihenfolge erfolgreich aktualisiert'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleUpdateGalleryDescription($pdo, $input) {
    $galleryId = $input['gallery_id'] ?? null;
    $description = $input['description'] ?? '';
    
    if (!$galleryId) {
        throw new Exception('Missing gallery_id');
    }
    
    $stmt = $pdo->prepare("UPDATE gallery SET description = ? WHERE id = ?");
    $success = $stmt->execute([$description, $galleryId]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Beschreibung erfolgreich aktualisiert'
        ]);
    } else {
        throw new Exception('Fehler beim Aktualisieren der Beschreibung');
    }
}
