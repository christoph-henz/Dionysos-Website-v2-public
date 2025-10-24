<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/vendor/autoload.php';
use Dionysosv2\Router;

ob_start();
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

require __DIR__ . '/src/Router.php';

$router = new Router;

$router->add("/", function() {
    require __DIR__ . '/src/Views/Home.php'; // korrekt: Slash ist schon da
});
$router->add("/reservation", function() {
    require __DIR__ . '/src/Views/Reservation.php';});
$router->add("/order", function() {
    require __DIR__ . '/src/Views/OrderMenu.php';});
$router->add("/order/submit", function() {
    require __DIR__ . '/src/Views/OrderSubmit.php';});
$router->add("/order/success", function() {
    require __DIR__ . '/src/Views/OrderSuccess.php';});
$router->add("/menu", function() {
    require __DIR__ . '/src/Views/MenuQR.php';});
$router->add("/impressum", function() {
    require __DIR__ . '/src/Views/Impressum.php';});
$router->add("/datenschutz", function() {
    require __DIR__ . '/src/Views/Datenschutz.php';});
$router->add("/agb", function() {
    require __DIR__ . '/src/Views/AGB.php';});

// Login/Logout Routen
$router->add("/login", function() {
    require_once __DIR__ . '/src/Controller/AuthController.php';
    $controller = new Dionysosv2\Controller\AuthController();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $controller->handleLogin();
    } else {
        $controller->showLogin();
    }
});

$router->add("/logout", function() {
    require_once __DIR__ .'/src/Controller/AuthController.php';
    $controller = new Dionysosv2\Controller\AuthController();
    $controller->logout();
});

// Admin Übersicht
$router->add("/admin", function() {
    require __DIR__ . '/src/Views/AdminOverview.php';});
// Artikel Management
$router->add("/admin/article-management", function() {
    require __DIR__ . '/src/Views/ArticleManagement.php';
});

// Debug Panel (nur für eingeloggte Benutzer)
$router->add("/debug", function() {
    require __DIR__ . '/src/Views/DebugOverview.php';});

// Admin Passwort ändern
$router->add("/admin/change-password", function() {
    require_once __DIR__ . '/src/Controller/AuthController.php';
    $controller = new Dionysosv2\Controller\AuthController();
    $controller->changePassword();
});

$router->add("/change-quantity", function() {
    require __DIR__ . '/public/api/cart_api.php';});

// Telegram Bot Routen
$router->add("/telegram/webhook", function() {
    require_once __DIR__ . '/src/Controller/TelegramController.php';
    $controller = new Dionysosv2\Controller\TelegramController();
    $controller->handleWebhook();
});

$router->add("/telegram/config", function() {
    require_once __DIR__ . '/src/Controller/TelegramController.php';
    $controller = new Dionysosv2\Controller\TelegramController();
    $controller->showConfig();
});

$router->add("/telegram/save-config", function() {
    require_once __DIR__ . '/src/Controller/TelegramController.php';
    $controller = new Dionysosv2\Controller\TelegramController();
    $controller->saveConfig();
});

$router->add("/telegram/setup-webhook", function() {
    require_once __DIR__ . '/src/Controller/TelegramController.php';
    $controller = new Dionysosv2\Controller\TelegramController();
    $controller->setupWebhook();
});

$router->add("/telegram/test", function() {
    require_once __DIR__ . '/src/Controller/TelegramController.php';
    $controller = new Dionysosv2\Controller\TelegramController();
    $controller->sendTestMessage();
});

$router->add("/telegram/webhook-info", function() {
    require_once __DIR__ . '/src/Controller/TelegramController.php';
    $controller = new Dionysosv2\Controller\TelegramController();
    $controller->getWebhookInfo();
});

$router->add("/telegram/remove-webhook", function() {
    require_once __DIR__ . '/src/Controller/TelegramController.php';
    $controller = new Dionysosv2\Controller\TelegramController();
    $controller->removeWebhook();
});

// Routing ausführen
$router->dispatch($path);
