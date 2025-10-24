<?php
namespace Dionysosv2\Views;

class CookieHandler
{
    public const COOKIE_EXPIRATION = 2592000; // 30 Tage in Sekunden
    public const ASKED_BEFORE_KEY = 'cookie_asked_before';
    public const ALLOW_ORDER_KEY = 'allow_order_cookies';
    public const ALLOW_GOOGLE_KEY = 'allow_google_cookies';

    private bool $askedBefore = false;
    private bool $allowOrder = false;
    private bool $allowGoogle = false;

    public function __construct()
    {
        $this->loadCookieSettings();
    }

    private function loadCookieSettings(): void
    {
        $this->askedBefore = isset($_COOKIE[self::ASKED_BEFORE_KEY]) ||
            (isset($_SESSION['cookie_settings_saved']) && $_SESSION['cookie_settings_saved'] === true);

        $this->allowOrder = isset($_COOKIE[self::ALLOW_ORDER_KEY]) &&
            $_COOKIE[self::ALLOW_ORDER_KEY] === 'true';

        $this->allowGoogle = isset($_COOKIE[self::ALLOW_GOOGLE_KEY]) &&
            $_COOKIE[self::ALLOW_GOOGLE_KEY] === 'true';
    }

    private function getCookieStyles(): string
    {
        return <<<'EOT'
        <style>
        .cookie-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.95);
            color: #fff;
            padding: 20px;
            z-index: 9999;
            transform: translateY(100%);
            animation: slidein 0.5s forwards;
        }
        
        @keyframes slidein {
            to {
                transform: translateY(0);
            }
        }        
        .cookie-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .cookie-content h2 {
            color: #ffab66;
            margin-bottom: 15px;
        }
        
        .cookie-options {
            margin: 20px 0;
        }
        
        .cookie-option {
            margin: 15px 0;
        }
        
        .cookie-description {
            font-size: 0.9em;
            color: #ccc;
            margin-top: 5px;
            margin-left: 25px;
        }
        
        .cookie-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .cookie-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .accept-all {
            background-color: #ffab66;
            color: #000;
        }
        
        .save-preferences {
            background-color: #333;
            color: #fff;
        }
        
        .accept-all:hover {
            background-color: #ff9933;
        }
        
        .save-preferences:hover {
            background-color: #444;
        }
        
        @media (max-width: 768px) {
            .cookie-buttons {
                flex-direction: column;
            }
            
            .cookie-buttons button {
                width: 100%;
            }
        }
        </style>
        EOT;
    }

    private function getCookieScript(): string
    {
        return <<< EOT
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const cookieForm = document.getElementById('cookieForm');
                const acceptAllBtn = document.querySelector('.accept-all');
                
                acceptAllBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('#cookieForm input[type="checkbox"]')
                        .forEach(checkbox => checkbox.checked = true);
                    cookieForm.submit();
                });
            });
            </script>
        EOT;
    }


    public function generateCookieBanner(): string
    {
        if ($this->askedBefore) {
            return '';
        }

        ob_start();
        ?>
        <div id="cookieBanner" class="cookie-banner">
            <div class="cookie-content">
                <h2>Cookie-Einstellungen</h2>
                <p>Wir verwenden Cookies, um Ihnen die bestmögliche Erfahrung auf unserer Website zu bieten.</p>

                <form id="cookieForm" method="post">
                    <div class="cookie-options">
                        <div class="cookie-option">
                            <label>
                                <input type="checkbox" name="<?= $this::ALLOW_ORDER_KEY ?>" value="1" checked>
                                Notwendige Cookies (Bestellung & Reservierung)
                            </label>
                            <p class="cookie-description">Diese Cookies sind für die Grundfunktionen der Website erforderlich.</p>
                        </div>

                        <div class="cookie-option">
                            <label>
                                <input type="checkbox" name="<?= $this::ALLOW_GOOGLE_KEY ?>" value="1">
                                Google Maps Cookies
                            </label>
                            <p class="cookie-description">Erlaubt das Laden von Google Maps für die Anfahrtskarte.</p>
                        </div>
                    </div>

                    <div class="cookie-buttons">
                        <button type="submit" class="accept-all">Alle akzeptieren</button>
                        <button type="submit" class="save-preferences">Auswahl speichern</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return $html . $this->getCookieStyles() . $this->getCookieScript();
    }

    public function generateCookieSettings(): void
    {
        $this->loadCookieSettings();
        $checkedOrder = $this->allowOrder ? 'checked' : '';
        $checkedGoogle = $this->allowGoogle ? 'checked' : '';
        ob_start();

        ?>
        <div class="cookie-settings">
            <h2>Cookie-Einstellungen</h2>
            <form method="post" class="cookie-form">
                <div class="cookie-options">
                    <div class="cookie-option">
                        <label>
                            <input type="checkbox" disabled="disabled" checked="checked" name="<?=$this::ALLOW_ORDER_KEY?>" value="1" {$checkedOrder}>
                            Notwendige Cookies (Bestellung & Reservierung)
                        </label>
                    </div>

                    <div class="cookie-option">
                        <label>
                            <input type="checkbox" name="<?=$this::ALLOW_GOOGLE_KEY?>" value="1" <?=$checkedGoogle?>>
                            Google Maps Cookies
                        </label>
                    </div>
                </div>

                <button type="submit" class="save-button">Einstellungen speichern</button>
            </form>
        </div>
        <?php
        $html = ob_get_clean();
        echo $html . $this->getCookieSettingsStyles() . $this->getCookieScript();
    }


    private function getCookieSettingsStyles(): string
    {
        return <<<'EOT'
            <style>
                .cookie-settings {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(0, 0, 0, 0.95);
                color: #000;
                padding: 20px;
                z-index: 9999;
                transform: translateY(100%);
                animation: slidein 0.5s forwards;
                border: #000 1px solid;
            }
            
            @keyframes slidein {
                to {
                    transform: translateY(0);
                }
            } 
            .cookie-settings {
                max-width: 800px;
                margin: 40px auto;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 8px;
            }
        
            .cookie-settings h2 {
                color: #333;
                margin-bottom: 20px;
            }
            
            .cookie-form {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            
            .cookie-option {
                margin: 10px 0;
            }
            
            .save-button {
                padding: 10px 20px;
                background-color: #ffab66;
                color: #000;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                align-self: flex-end;
            }
            
            .save-button:hover {
                background-color: #ff9933;
            }
            </style>
        EOT;
    }

    // Getter und Setter Methoden
    public function isAskedBefore(): bool
    {
        return $this->askedBefore;
    }

    public function setAskedBefore(bool $asked): void
    {
        $this->askedBefore = $asked;
        setcookie(self::ASKED_BEFORE_KEY, '1', time() + self::COOKIE_EXPIRATION, '/');
    }

    public function isAllowOrder(): bool
    {
        return $this->allowOrder;
    }

    public function setAllowOrder(bool $allow): void
    {
        $this->allowOrder = $allow;
        setcookie(self::ALLOW_ORDER_KEY, $allow ? 'true' : 'false', time() + self::COOKIE_EXPIRATION, '/');
    }

    public function isAllowGoogle(): bool
    {
        return $this->allowGoogle;
    }

    public function setAllowGoogle(bool $allow): void
    {
        $this->allowGoogle = $allow;
        setcookie(self::ALLOW_GOOGLE_KEY, $allow ? 'true' : 'false', time() + self::COOKIE_EXPIRATION, '/');
    }
}