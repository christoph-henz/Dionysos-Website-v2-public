<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONS-Anfragen für CORS-Preflight behandeln
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

// Authentifizierung prüfen
session_start();
if (!isset($_SESSION['admin_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit();
}

// Datenbankverbindung
try {
    $host = $_SERVER['HTTP_HOST'];
    $isLocal = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
    
    if ($isLocal) {
        // SQLite für lokale Entwicklung
        $database = new PDO('sqlite:' . __DIR__ . '/../../database.db');
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        // MySQL für Produktion
        $database = new PDO(
            'mysql:host=localhost;dbname=dionysos_db;charset=utf8mb4',
            'dionysos_user',
            'dionysos_password',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
    exit();
}

// Aktion bestimmen
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add_to_gallery':
            echo json_encode(handleAddToGallery($database, $isLocal));
            break;
            
        case 'remove_from_gallery':
            echo json_encode(handleRemoveFromGallery($database, $isLocal));
            break;
            
        case 'edit_gallery_image':
            echo json_encode(handleEditGalleryImage($database, $isLocal));
            break;
            
        case 'move_gallery_image':
            echo json_encode(handleMoveGalleryImage($database, $isLocal));
            break;
            
        case 'toggle_gallery_image':
            echo json_encode(handleToggleGalleryImage($database, $isLocal));
            break;
            
        case 'upload_website_image':
            echo json_encode(handleUploadWebsiteImage($database, $isLocal));
            break;
            
        case 'edit_website_image':
            echo json_encode(handleEditWebsiteImage($database, $isLocal));
            break;
            
        case 'toggle_website_image':
            echo json_encode(handleToggleWebsiteImage($database, $isLocal));
            break;
            
        case 'delete_website_image':
            echo json_encode(handleDeleteWebsiteImage($database, $isLocal));
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Fehler: ' . $e->getMessage()]);
}

// Galerie-Management Funktionen

function handleAddToGallery($database, $isLocal) {
    $imageId = $_POST['image_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    
    if (!$imageId) {
        return ['success' => false, 'message' => 'Bild-ID fehlt'];
    }
    
    // Prüfen ob Bild bereits in Galerie ist
    $galleryTable = $isLocal ? 'Gallery' : 'gallery';
    $stmt = $database->prepare("SELECT id FROM {$galleryTable} WHERE image_id = ?");
    $stmt->execute([$imageId]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Bild ist bereits in der Galerie'];
    }
    
    // Zur Galerie hinzufügen
    $stmt = $database->prepare("
        INSERT INTO {$galleryTable} (image_id, description, display_order, active, created_at) 
        VALUES (?, ?, ?, 1, " . ($isLocal ? "datetime('now')" : "NOW()") . ")
    ");
    $stmt->execute([$imageId, $description, $displayOrder]);
    
    return ['success' => true, 'message' => 'Bild zur Galerie hinzugefügt'];
}

function handleRemoveFromGallery($database, $isLocal) {
    $galleryId = $_POST['gallery_id'] ?? null;
    
    if (!$galleryId) {
        return ['success' => false, 'message' => 'Galerie-ID fehlt'];
    }
    
    $galleryTable = $isLocal ? 'Gallery' : 'gallery';
    $stmt = $database->prepare("DELETE FROM {$galleryTable} WHERE id = ?");
    $stmt->execute([$galleryId]);
    
    return ['success' => true, 'message' => 'Bild aus Galerie entfernt'];
}

function handleEditGalleryImage($database, $isLocal) {
    $galleryId = $_POST['gallery_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $displayOrder = (int)($_POST['display_order'] ?? 1);
    
    if (!$galleryId) {
        return ['success' => false, 'message' => 'Galerie-ID fehlt'];
    }
    
    $galleryTable = $isLocal ? 'Gallery' : 'gallery';
    $stmt = $database->prepare("
        UPDATE {$galleryTable} 
        SET description = ?, display_order = ? 
        WHERE id = ?
    ");
    $stmt->execute([$description, $displayOrder, $galleryId]);
    
    return ['success' => true, 'message' => 'Galerie-Bild aktualisiert'];
}

function handleMoveGalleryImage($database, $isLocal) {
    $galleryId = $_POST['gallery_id'] ?? null;
    $direction = $_POST['direction'] ?? '';
    
    if (!$galleryId || !in_array($direction, ['up', 'down'])) {
        return ['success' => false, 'message' => 'Ungültige Parameter'];
    }
    
    $galleryTable = $isLocal ? 'Gallery' : 'gallery';
    
    // Aktuelle Position ermitteln
    $stmt = $database->prepare("SELECT display_order FROM {$galleryTable} WHERE id = ?");
    $stmt->execute([$galleryId]);
    $currentOrder = $stmt->fetchColumn();
    
    if ($currentOrder === false) {
        return ['success' => false, 'message' => 'Galerie-Eintrag nicht gefunden'];
    }
    
    $newOrder = $direction === 'up' ? $currentOrder - 1 : $currentOrder + 1;
    
    if ($newOrder < 1) {
        return ['success' => false, 'message' => 'Bereits an erster Position'];
    }
    
    // Position tauschen
    $database->beginTransaction();
    
    try {
        // Temporäre Position für Tausch
        $stmt = $database->prepare("UPDATE {$galleryTable} SET display_order = -1 WHERE display_order = ?");
        $stmt->execute([$newOrder]);
        
        // Aktuelles Element verschieben
        $stmt = $database->prepare("UPDATE {$galleryTable} SET display_order = ? WHERE id = ?");
        $stmt->execute([$newOrder, $galleryId]);
        
        // Anderes Element auf alte Position
        $stmt = $database->prepare("UPDATE {$galleryTable} SET display_order = ? WHERE display_order = -1");
        $stmt->execute([$currentOrder]);
        
        $database->commit();
        return ['success' => true, 'message' => 'Position geändert'];
    } catch (Exception $e) {
        $database->rollBack();
        return ['success' => false, 'message' => 'Fehler beim Verschieben: ' . $e->getMessage()];
    }
}

function handleToggleGalleryImage($database, $isLocal) {
    $galleryId = $_POST['gallery_id'] ?? null;
    
    if (!$galleryId) {
        return ['success' => false, 'message' => 'Galerie-ID fehlt'];
    }
    
    $galleryTable = $isLocal ? 'Gallery' : 'gallery';
    $stmt = $database->prepare("
        UPDATE {$galleryTable} 
        SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END 
        WHERE id = ?
    ");
    $stmt->execute([$galleryId]);
    
    return ['success' => true, 'message' => 'Status geändert'];
}

// Website-Bilder-Management Funktionen

function handleUploadWebsiteImage($database, $isLocal) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Kein Bild hochgeladen oder Upload-Fehler'];
    }
    
    $uploadDir = __DIR__ . '/../assets/img/';
    $file = $_FILES['image'];
    $altText = $_POST['alt_text'] ?? '';
    $usageContext = $_POST['usage_context'] ?? 'general';
    
    // Datei-Validierung
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Ungültiger Dateityp. Nur JPG, PNG, WEBP und GIF sind erlaubt.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Datei zu groß. Maximum 5MB.'];
    }
    
    // Sicherer Dateiname
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('website_') . '.' . strtolower($extension);
    $targetPath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Fehler beim Speichern der Datei'];
    }
    
    // In Datenbank speichern
    try {
        $websiteImagesTable = $isLocal ? 'WebsiteImages' : 'website_images';
        $stmt = $database->prepare("
            INSERT INTO {$websiteImagesTable} (name, alt_text, usage_context, active, created_at) 
            VALUES (?, ?, ?, 1, " . ($isLocal ? "datetime('now')" : "NOW()") . ")
        ");
        $stmt->execute([$filename, $altText, $usageContext]);
        
        return ['success' => true, 'message' => 'Website-Bild erfolgreich hochgeladen', 'filename' => $filename];
    } catch (Exception $e) {
        // Datei löschen bei Datenbankfehler
        unlink($targetPath);
        return ['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()];
    }
}

function handleEditWebsiteImage($database, $isLocal) {
    $imageId = $_POST['image_id'] ?? null;
    $altText = $_POST['alt_text'] ?? '';
    $usageContext = $_POST['usage_context'] ?? 'general';
    
    if (!$imageId) {
        return ['success' => false, 'message' => 'Bild-ID fehlt'];
    }
    
    $websiteImagesTable = $isLocal ? 'WebsiteImages' : 'website_images';
    $stmt = $database->prepare("
        UPDATE {$websiteImagesTable} 
        SET alt_text = ?, usage_context = ? 
        WHERE id = ?
    ");
    $stmt->execute([$altText, $usageContext, $imageId]);
    
    return ['success' => true, 'message' => 'Website-Bild aktualisiert'];
}

function handleToggleWebsiteImage($database, $isLocal) {
    $imageId = $_POST['image_id'] ?? null;
    
    if (!$imageId) {
        return ['success' => false, 'message' => 'Bild-ID fehlt'];
    }
    
    $websiteImagesTable = $isLocal ? 'WebsiteImages' : 'website_images';
    $stmt = $database->prepare("
        UPDATE {$websiteImagesTable} 
        SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END 
        WHERE id = ?
    ");
    $stmt->execute([$imageId]);
    
    return ['success' => true, 'message' => 'Status geändert'];
}

function handleDeleteWebsiteImage($database, $isLocal) {
    $imageId = $_POST['image_id'] ?? null;
    
    if (!$imageId) {
        return ['success' => false, 'message' => 'Bild-ID fehlt'];
    }
    
    $websiteImagesTable = $isLocal ? 'WebsiteImages' : 'website_images';
    
    // Dateinamen ermitteln
    $stmt = $database->prepare("SELECT name FROM {$websiteImagesTable} WHERE id = ?");
    $stmt->execute([$imageId]);
    $filename = $stmt->fetchColumn();
    
    if (!$filename) {
        return ['success' => false, 'message' => 'Bild nicht gefunden'];
    }
    
    // Aus Datenbank löschen
    $stmt = $database->prepare("DELETE FROM {$websiteImagesTable} WHERE id = ?");
    $stmt->execute([$imageId]);
    
    // Datei löschen
    $filePath = __DIR__ . '/../assets/img/' . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    return ['success' => true, 'message' => 'Website-Bild gelöscht'];
}
