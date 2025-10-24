<?php

namespace Dionysosv2\Controller;

use Dionysosv2\Views\Page;

class AuthController extends Page
{
    public function __construct()
    {
        parent::__construct(); // Initialisiert $_database und $isLocal
    }

    public function showLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Wenn bereits eingeloggt, weiterleiten
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            header('Location: /admin');
            exit;
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        include __DIR__ . '/../Views/Login.php';
    }

    public function handleLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Benutzername und Passwort sind erforderlich';
            header('Location: /login');
            exit;
        }

        // Admin-Credentials aus Datenbank prüfen
        $stmt = $this->_database->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_username'");
        $stmt->execute();
        $adminUsername = $stmt->fetchColumn();

        $stmt = $this->_database->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_password_hash'");
        $stmt->execute();
        $adminPasswordHash = $stmt->fetchColumn();

        // Falls noch keine Admin-Credentials existieren, Standard setzen
        if (!$adminUsername || !$adminPasswordHash) {
            $this->createDefaultAdmin();
            $adminUsername = 'admin';
            $adminPasswordHash = password_hash('admin123', PASSWORD_DEFAULT);
        }

        if ($username === $adminUsername && password_verify($password, $adminPasswordHash)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            
            // Redirect zu ursprünglich angeforderte Seite oder Admin-Dashboard
            $redirect = $_SESSION['login_redirect'] ?? '/admin';
            unset($_SESSION['login_redirect']);
            
            header('Location: ' . $redirect);
            exit;
        } else {
            $_SESSION['login_error'] = 'Ungültige Anmeldedaten';
            header('Location: /login');
            exit;
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function requireAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            // Aktuelle URL für Redirect nach Login speichern
            $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
    }

    private function createDefaultAdmin()
    {
        // Standard Admin-Account erstellen
        $defaultUsername = 'admin';
        $defaultPassword = 'admin123';
        $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

        // Fallback für Umgebungserkennung falls $this->isLocal nicht initialisiert ist
        $isLocalEnv = isset($this->isLocal) ? $this->isLocal : (file_exists(__DIR__ . '/../../database.db'));

        if ($isLocalEnv) {
            // SQLite Syntax
            // Username speichern/aktualisieren
            $stmt = $this->_database->prepare("
                INSERT OR REPLACE INTO settings (setting_key, setting_value, category) 
                VALUES ('admin_username', :username, 'auth')
            ");
            $stmt->bindValue(':username', $defaultUsername, \PDO::PARAM_STR);
            $stmt->execute();
            
            // Password Hash speichern/aktualisieren
            $stmt = $this->_database->prepare("
                INSERT OR REPLACE INTO settings (setting_key, setting_value, category) 
                VALUES ('admin_password_hash', :password_hash, 'auth')
            ");
            $stmt->bindValue(':password_hash', $passwordHash, \PDO::PARAM_STR);
            $stmt->execute();
        } else {
            // MySQL Syntax
            // Username speichern/aktualisieren
            $stmt = $this->_database->prepare("
                INSERT INTO settings (setting_key, setting_value, category) 
                VALUES ('admin_username', :username, 'auth')
                ON DUPLICATE KEY UPDATE setting_value = :username
            ");
            $stmt->bindValue(':username', $defaultUsername, \PDO::PARAM_STR);
            $stmt->execute();
            
            // Password Hash speichern/aktualisieren
            $stmt = $this->_database->prepare("
                INSERT INTO settings (setting_key, setting_value, category) 
                VALUES ('admin_password_hash', :password_hash, 'auth')
                ON DUPLICATE KEY UPDATE setting_value = :password_hash
            ");
            $stmt->bindValue(':password_hash', $passwordHash, \PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    public function showChangePassword()
    {
        $this->requireAuth();
        
        // Verhindere doppelten Aufruf
        define('PREVENT_DIRECT_ACCESS', true);
        
        // View-Klasse direkt aufrufen
        require_once __DIR__ . '/../Views/ChangePassword.php';
        \Dionysosv2\Views\ChangePassword::main();
    }

    public function handleChangePassword()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/change-password');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['password_error'] = 'Alle Felder sind erforderlich';
            header('Location: /admin/change-password');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['password_error'] = 'Neue Passwörter stimmen nicht überein';
            header('Location: /admin/change-password');
            exit;
        }

        if (strlen($newPassword) < 6) {
            $_SESSION['password_error'] = 'Passwort muss mindestens 6 Zeichen lang sein';
            header('Location: /admin/change-password');
            exit;
        }

        // Aktuelles Passwort prüfen
        $stmt = $this->_database->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_password_hash'");
        $stmt->execute();
        $currentHash = $stmt->fetchColumn();

        if (!password_verify($currentPassword, $currentHash)) {
            $_SESSION['password_error'] = 'Aktuelles Passwort ist falsch';
            header('Location: /admin/change-password');
            exit;
        }

        // Neues Passwort speichern
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->_database->prepare("
            UPDATE settings 
            SET setting_value = :new_hash
            WHERE setting_key = 'admin_password_hash'
        ");
        $stmt->bindValue(':new_hash', $newHash, \PDO::PARAM_STR);
        $stmt->execute();

        $_SESSION['password_success'] = 'Passwort erfolgreich geändert';
        header('Location: /admin/change-password');
        exit;
    }

    public function changePassword()
    {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleChangePassword();
        } else {
            $this->showChangePassword();
        }
    }
}
