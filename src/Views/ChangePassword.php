<?php

namespace Dionysosv2\Views;

use Exception;

// Page-Klasse laden
require_once __DIR__ . '/Page.php';

class ChangePassword extends Page
{
    protected function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public static function main(): void
    {
        try {
            $page = new ChangePassword();
            $page->processReceivedData();
            $page->generateView();
        } catch (Exception $e) {
            header("Content-type: text/html; charset=UTF-8");
            echo $e->getMessage();
        }
    }

    protected function processReceivedData(): void
    {
        parent::processReceivedData();
    }

    protected function additionalMetaData(): void
    {
        // Zusätzliche Meta-Tags für Admin-Bereich
        echo '<meta name="robots" content="noindex, nofollow">';
        echo '<link rel="icon" type="image/x-icon" href="public/assets/img/favicon.ico">';
        echo '<link rel="stylesheet" href="public/assets/css/home.css">';
    }

    protected function generateView(): void
    {
        $this->generatePageHeader('Passwort ändern');
        $this->generateAdminHeader();
        $this->generateMainBody();
        $this->generatePageFooter();
    }

    private function generateAdminHeader(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $username = $_SESSION['admin_username'] ?? 'Admin';
        
        echo '<div class="admin-header">';
        echo '<div class="admin-user-info">';
        echo '<span>👤 Angemeldet als: <strong>' . htmlspecialchars($username) . '</strong></span>';
        echo '<div class="admin-actions">';
        echo '<a href="/admin" class="btn btn-secondary">🏠 Zurück zur Übersicht</a>';
        echo '<a href="/logout" class="btn btn-danger">🚪 Abmelden</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    protected function generateMainBody(): void
    {
        echo '<div class="change-password-container">';
        echo '<h1>🔐 Passwort ändern</h1>';
        
        $this->generateMessages();
        $this->generatePasswordForm();
        $this->generatePasswordRequirements();
        
        echo '</div>';
        
        $this->generateStyles();
        $this->generateJavaScript();
    }

    private function generateMessages(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['password_error'])) {
            echo '<div class="alert alert-error">';
            echo '❌ ' . htmlspecialchars($_SESSION['password_error']);
            echo '</div>';
            unset($_SESSION['password_error']);
        }
        
        if (isset($_SESSION['password_success'])) {
            echo '<div class="alert alert-success">';
            echo '✅ ' . htmlspecialchars($_SESSION['password_success']);
            echo '</div>';
            unset($_SESSION['password_success']);
        }
    }

    private function generatePasswordForm(): void
    {
        echo '<div class="password-form-card">';
        echo '<form method="POST" action="/admin/change-password" id="passwordForm">';
        
        echo '<div class="form-group">';
        echo '<label for="current_password">Aktuelles Passwort:</label>';
        echo '<input type="password" id="current_password" name="current_password" required>';
        echo '<span class="toggle-password" onclick="togglePassword(\'current_password\')">👁️</span>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="new_password">Neues Passwort:</label>';
        echo '<input type="password" id="new_password" name="new_password" required minlength="6">';
        echo '<span class="toggle-password" onclick="togglePassword(\'new_password\')">👁️</span>';
        echo '<div class="password-strength" id="password-strength"></div>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="confirm_password">Neues Passwort bestätigen:</label>';
        echo '<input type="password" id="confirm_password" name="confirm_password" required minlength="6">';
        echo '<span class="toggle-password" onclick="togglePassword(\'confirm_password\')">👁️</span>';
        echo '<div class="password-match" id="password-match"></div>';
        echo '</div>';
        
        echo '<div class="form-actions">';
        echo '<button type="submit" class="btn btn-primary" id="submitBtn">🔐 Passwort ändern</button>';
        echo '<a href="/admin" class="btn btn-secondary">❌ Abbrechen</a>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
    }

    private function generatePasswordRequirements(): void
    {
        echo '<div class="password-requirements">';
        echo '<h3>📋 Passwort-Anforderungen</h3>';
        echo '<ul>';
        echo '<li>Mindestens 6 Zeichen lang</li>';
        echo '<li>Verwendung von Groß- und Kleinbuchstaben empfohlen</li>';
        echo '<li>Verwendung von Zahlen empfohlen</li>';
        echo '<li>Verwendung von Sonderzeichen empfohlen</li>';
        echo '<li>Nicht dasselbe wie das aktuelle Passwort</li>';
        echo '</ul>';
        echo '</div>';
    }

    private function generateStyles(): void
    {
        echo <<<EOT
        <style>
            .admin-header {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 1rem 1.5rem;
                margin-bottom: 2rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .admin-user-info {
                display: flex;
                align-items: center;
                gap: 1rem;
                width: 100%;
                justify-content: space-between;
            }

            .admin-actions {
                display: flex;
                gap: 0.5rem;
            }

            .change-password-container {
                max-width: 600px;
                margin: 0 auto;
                padding: 2rem;
            }

            .change-password-container h1 {
                text-align: center;
                color: #333;
                margin-bottom: 2rem;
                font-size: 2rem;
            }

            .alert {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1.5rem;
                font-weight: 500;
            }

            .alert-error {
                background: #ffebee;
                color: #c62828;
                border: 1px solid #ffcdd2;
            }

            .alert-success {
                background: #e8f5e8;
                color: #2e7d32;
                border: 1px solid #c8e6c9;
            }

            .password-form-card {
                background: white;
                border-radius: 12px;
                padding: 2rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                margin-bottom: 2rem;
            }

            .form-group {
                margin-bottom: 1.5rem;
                position: relative;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: bold;
                color: #333;
            }

            .form-group input {
                width: 100%;
                padding: 12px 40px 12px 12px;
                border: 2px solid #e1e5e9;
                border-radius: 8px;
                font-size: 16px;
                transition: border-color 0.3s ease;
                box-sizing: border-box;
            }

            .form-group input:focus {
                outline: none;
                border-color: #ffab66;
            }

            .toggle-password {
                position: absolute;
                right: 12px;
                top: 38px;
                cursor: pointer;
                user-select: none;
                font-size: 18px;
                opacity: 0.6;
                transition: opacity 0.2s;
            }

            .toggle-password:hover {
                opacity: 1;
            }

            .password-strength {
                margin-top: 0.5rem;
                font-size: 0.9rem;
                font-weight: bold;
            }

            .strength-weak {
                color: #f44336;
            }

            .strength-medium {
                color: #ff9800;
            }

            .strength-strong {
                color: #4caf50;
            }

            .password-match {
                margin-top: 0.5rem;
                font-size: 0.9rem;
                font-weight: bold;
            }

            .match-no {
                color: #f44336;
            }

            .match-yes {
                color: #4caf50;
            }

            .form-actions {
                display: flex;
                gap: 1rem;
                margin-top: 2rem;
            }

            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: bold;
                font-size: 16px;
                text-decoration: none;
                display: inline-block;
                text-align: center;
                transition: all 0.2s;
                flex: 1;
            }

            .btn-primary {
                background: #ffab66;
                color: white;
            }

            .btn-primary:hover:not(:disabled) {
                background: #ff9240;
                transform: translateY(-2px);
            }

            .btn-primary:disabled {
                background: #ccc;
                cursor: not-allowed;
            }

            .btn-secondary {
                background: #f5f5f5;
                color: #333;
                border: 1px solid #ddd;
            }

            .btn-secondary:hover {
                background: #e0e0e0;
            }

            .btn-danger {
                background: #f44336;
                color: white;
            }

            .btn-danger:hover {
                background: #da190b;
            }

            .password-requirements {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 1.5rem;
                border-left: 4px solid #17a2b8;
            }

            .password-requirements h3 {
                margin-top: 0;
                color: #17a2b8;
                margin-bottom: 1rem;
            }

            .password-requirements ul {
                list-style-type: none;
                padding: 0;
                margin: 0;
            }

            .password-requirements li {
                padding: 0.25rem 0;
                position: relative;
                padding-left: 1.5rem;
            }

            .password-requirements li:before {
                content: "✓";
                position: absolute;
                left: 0;
                color: #4caf50;
                font-weight: bold;
            }

            @media (max-width: 768px) {
                .change-password-container {
                    padding: 1rem;
                }

                .password-form-card {
                    padding: 1.5rem;
                }

                .form-actions {
                    flex-direction: column;
                }

                .admin-actions {
                    flex-direction: column;
                    gap: 0.25rem;
                }
            }
        </style>
        EOT;
    }

    private function generateJavaScript(): void
    {
        echo <<<EOT
        <script>
            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
                field.setAttribute('type', type);
            }

            function checkPasswordStrength(password) {
                let strength = 0;
                let feedback = '';

                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;

                if (strength <= 2) {
                    feedback = 'Schwach 🔴';
                    return { level: 'weak', text: feedback };
                } else if (strength <= 4) {
                    feedback = 'Mittel 🟡';
                    return { level: 'medium', text: feedback };
                } else {
                    feedback = 'Stark 🟢';
                    return { level: 'strong', text: feedback };
                }
            }

            function validatePasswords() {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const currentPassword = document.getElementById('current_password').value;
                const submitBtn = document.getElementById('submitBtn');

                // Passwort-Stärke prüfen
                const strengthResult = checkPasswordStrength(newPassword);
                const strengthElement = document.getElementById('password-strength');
                strengthElement.textContent = strengthResult.text;
                strengthElement.className = 'password-strength strength-' + strengthResult.level;

                // Passwort-Übereinstimmung prüfen
                const matchElement = document.getElementById('password-match');
                if (confirmPassword === '') {
                    matchElement.textContent = '';
                    matchElement.className = 'password-match';
                } else if (newPassword === confirmPassword) {
                    matchElement.textContent = 'Passwörter stimmen überein ✅';
                    matchElement.className = 'password-match match-yes';
                } else {
                    matchElement.textContent = 'Passwörter stimmen nicht überein ❌';
                    matchElement.className = 'password-match match-no';
                }

                // Submit-Button aktivieren/deaktivieren
                const isValid = currentPassword.length > 0 && 
                               newPassword.length >= 6 && 
                               newPassword === confirmPassword &&
                               newPassword !== currentPassword;
                
                submitBtn.disabled = !isValid;
            }

            document.addEventListener('DOMContentLoaded', function() {
                const inputs = ['current_password', 'new_password', 'confirm_password'];
                inputs.forEach(id => {
                    document.getElementById(id).addEventListener('input', validatePasswords);
                });

                // Initial validation
                validatePasswords();

                // Form submission
                document.getElementById('passwordForm').addEventListener('submit', function(e) {
                    const newPassword = document.getElementById('new_password').value;
                    const currentPassword = document.getElementById('current_password').value;
                    
                    if (newPassword === currentPassword) {
                        e.preventDefault();
                        alert('Das neue Passwort darf nicht dasselbe wie das aktuelle Passwort sein!');
                        return false;
                    }
                });
            });
        </script>
        EOT;
    }
}

// Aufruf am Ende für direkten Include
if (!defined('PREVENT_DIRECT_ACCESS')) {
    ChangePassword::main();
}
