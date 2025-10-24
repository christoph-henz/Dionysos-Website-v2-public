<?php

namespace Dionysosv2\Views;

use Exception;

// Authentifizierung prüfen
require_once __DIR__ . '/../Controller/AuthController.php';
use Dionysosv2\Controller\AuthController;
$authController = new AuthController();
$authController->requireAuth();

class ArticleManagement extends Page
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
        // UTF-8 Header setzen
        header('Content-Type: text/html; charset=UTF-8');
        
        try {
            $page = new ArticleManagement();
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
        // Option hinzufügen
        if (isset($_POST['add_option'])) {
            $optionController = new \Dionysosv2\Controller\OptionController();
            $name = $_POST['option_name'] ?? '';
            $desc = $_POST['option_description'] ?? '';
            $price = $_POST['option_price'] ?? 0.0;
            $isDefault = isset($_POST['option_default']) ? 1 : 0;
            $groupId = $_POST['option_group_id'] ?? 0;
            if ($name && $groupId) {
                $stmt = $optionController->_database->prepare("INSERT INTO options (option_group_id, name, description, price_modifier, is_default) VALUES (:group_id, :name, :desc, :price, :is_default)");
                $stmt->bindParam(':group_id', $groupId, \PDO::PARAM_INT);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':desc', $desc);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':is_default', $isDefault, \PDO::PARAM_INT);
                $stmt->execute();
            }
        }

        // Option bearbeiten
        if (isset($_POST['edit_option'])) {
            $optionController = new \Dionysosv2\Controller\OptionController();
            $optionId = $_POST['option_id'] ?? 0;
            $name = $_POST['option_name'] ?? '';
            $desc = $_POST['option_description'] ?? '';
            $price = $_POST['option_price'] ?? 0.0;
            $isDefault = isset($_POST['option_default']) ? 1 : 0;
            if ($optionId && $name) {
                $stmt = $optionController->_database->prepare("UPDATE options SET name = :name, description = :desc, price_modifier = :price, is_default = :is_default WHERE id = :id");
                $stmt->bindParam(':id', $optionId, \PDO::PARAM_INT);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':desc', $desc);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':is_default', $isDefault, \PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    
        parent::processReceivedData();
        // PDF-Generierung auf Button-Klick
        if (isset($_POST['regen_pdf_de'])) {
            $builder = new \Dionysosv2\Controller\MenuBuilder();
            $builder->generatePdf();
        }
        if (isset($_POST['regen_pdf_en'])) {
            $builder = new \Dionysosv2\Controller\MenuBuilder();
            $builder->generateEnglishPdf();
        }
            // Artikel hinzufügen
            if (isset($_POST['add_article'])) {
                $controller = new \Dionysosv2\Controller\ArticleController();
                $controller->addArticle(
                    $_POST['plu'],
                    $_POST['category_id'],
                    $_POST['name'],
                    $_POST['price'],
                    $_POST['description'] ?? '',
                    $_POST['name_en'] ?? '',
                    $_POST['description_en'] ?? ''
                );
            }
            // Artikel löschen
            if (isset($_POST['delete_article'])) {
                $controller = new \Dionysosv2\Controller\ArticleController();
                $controller->deleteArticle($_POST['article_id']);
            }
            // Artikel bearbeiten
            if (isset($_POST['edit_article'])) {
                $controller = new \Dionysosv2\Controller\ArticleController();
                $controller->updateArticle(
                    $_POST['article_id'],
                    $_POST['plu'],
                    $_POST['category_id'],
                    $_POST['name'],
                    $_POST['price'],
                    $_POST['description'] ?? '',
                    $_POST['name_en'] ?? '',
                    $_POST['description_en'] ?? ''
                );
            }
    }

    public function additionalMetaData(): void
    {
        // Zusätzliche Meta-Tags für Admin-Bereich
        echo '<meta name="robots" content="noindex, nofollow">';
        echo '<link rel="icon" type="image/x-icon" href="/public/assets/img/favicon.ico">';
        echo '<link rel="stylesheet" href="/public/assets/css/home.css">';
        echo '<link rel="stylesheet" href="/public/assets/css/admin.css">';
        echo '<link rel="stylesheet" href="/public/assets/css/adminarticle.css">';
        echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>';
        echo '<style>.article-details-row { display: none; background: #f9f9f9; } .article-row.active { background: #eaf6ff; cursor: pointer; } .article-details-row td { padding: 16px 8px; }</style>';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".article-row").forEach(function(row) {
                row.addEventListener("click", function() {
                    var details = row.nextElementSibling;
                    if (details && details.classList.contains("article-details-row")) {
                        var isOpen = details.style.display === "table-row";
                        details.style.display = isOpen ? "none" : "table-row";
                        row.classList.toggle("active", !isOpen);
                        details.classList.toggle("active", !isOpen);
                    }
                });
            });
        });
        </script>';
    }

    protected function generateView(): void
    {
        $this->generatePageHeader('Admin Übersicht');
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
        echo '<a id="auto-refresh-btn" href="/admin" class="btn btn-success">Zurück</a>';
        echo '<a href="/logout" class="btn btn-danger">🚪 Logout</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    protected function generateMainBody(): void
    {
        echo '<div class="admin-container">';
        echo '<h1 class="admin-title">🎛️ Article Verwaltung</h1>';
            $controller = new \Dionysosv2\Controller\ArticleController();
            $articles = $controller->getAllArticles();
            echo '<h2>Artikel hinzufügen</h2>';
            echo '<form method="post" style="margin-bottom:2em;">';
            echo '<input type="text" name="plu" placeholder="PLU" required> ';
            echo '<input type="number" name="category_id" placeholder="Kategorie-ID" required> ';
            echo '<input type="text" name="name" placeholder="Name" required> ';
            echo '<input type="number" step="0.01" name="price" placeholder="Preis" required> ';
            echo '<input type="text" name="description" placeholder="Beschreibung"> ';
            echo '<input type="text" name="name_en" placeholder="Name (EN)"> ';
            echo '<input type="text" name="description_en" placeholder="Beschreibung (EN)"> ';
            echo '<button type="submit" name="add_article" class="btn btn-success">Artikel hinzufügen</button>';
            echo '</form>';

            echo '<h2>Artikel bearbeiten/löschen</h2>';
            echo '<table class="admin-table"><tr><th>ID</th><th>PLU</th><th>Name</th><th>Preis</th><th>Kategorie</th><th>Aktionen</th></tr>';
            $catController = new \Dionysosv2\Controller\ArticleCategoryController();
            $categories = $catController->getAllCategories();
            $optionController = new \Dionysosv2\Controller\OptionController();
            foreach ($articles as $article) {
                // Hauptzeile
                echo '<tr class="article-row" style="cursor:pointer;">';
                echo '<td>' . htmlspecialchars($article->getId()) . '</td>';
                echo '<td>' . htmlspecialchars($article->getPlu()) . '</td>';
                echo '<td>' . htmlspecialchars($article->getName()) . '</td>';
                echo '<td>' . htmlspecialchars($article->getPrice()) . '</td>';
                echo '<td>' . htmlspecialchars($article->getCategory()->getName()) . '</td>';
                echo '<td>Details & Bearbeiten</td>';
                echo '</tr>';

                // Details-/Bearbeitungszeile
                echo '<tr class="article-details-row">';
                echo '<td colspan="6">';
                echo '<form method="post" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">';
                echo '<input type="hidden" name="article_id" value="' . htmlspecialchars($article->getId()) . '">';
                echo '<label>PLU:<input type="text" name="plu" value="' . htmlspecialchars($article->getPlu()) . '" required></label> ';
                echo '<label>Name:<input type="text" name="name" value="' . htmlspecialchars($article->getName()) . '" required></label> ';
                echo '<label>Preis:<input type="number" step="0.01" name="price" value="' . htmlspecialchars($article->getPrice()) . '" required></label> ';
                echo '<label>Kategorie:<select name="category_id" required style="min-width:120px;">';
                foreach ($categories as $cat) {
                    $selected = ($cat->getName() == $article->getCategory()->getName()) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($cat->getId()) . '" ' . $selected . '>' . htmlspecialchars($cat->getName()) . '</option>';
                }
                echo '</select></label> ';
                echo '<label>Beschreibung:<input type="text" name="description" value="' . htmlspecialchars($article->getDescription()) . '"></label> ';
                echo '<label>Name (EN):<input type="text" name="name_en" value="' . htmlspecialchars($article->getNameEn()) . '"></label> ';
                echo '<label>Beschreibung (EN):<input type="text" name="description_en" value="' . htmlspecialchars($article->getDescriptionEn()) . '"></label> ';
                echo '<button type="submit" name="edit_article" class="btn btn-primary">Speichern</button> ';
                echo '</form>';
                echo '<form method="post" style="display:inline-block; margin-left:8px;">';
                echo '<input type="hidden" name="article_id" value="' . htmlspecialchars($article->getId()) . '">';
                echo '<button type="submit" name="delete_article" class="btn btn-danger" onclick="return confirm(\'Wirklich löschen?\');">Löschen</button>';
                echo '</form>';

                // Optionen anzeigen und bearbeiten
                $optionGroups = $optionController->getOptionGroupsByArticleId($article->getId());
                foreach ($optionGroups as $group) {
                    echo '<div style="margin:18px 0 8px 0; padding:10px 16px; background:#f7f7fa; border-radius:8px;">';
                    echo '<strong>' . htmlspecialchars($group->getName()) . '</strong> <span style="color:#888;">(' . htmlspecialchars($group->getDescription()) . ')</span>';
                    echo '<ul style="margin:8px 0 12px 0; padding-left:18px;">';
                    foreach ($group->getOptions() as $option) {
                        echo '<li style="margin-bottom:8px;">';
                        echo '<form method="post" style="display:inline-block; margin-right:8px;">';
                        echo '<input type="hidden" name="option_id" value="' . htmlspecialchars($option->getId()) . '">';
                        echo '<input type="text" name="option_name" value="' . htmlspecialchars($option->getName()) . '" style="min-width:120px;"> ';
                        echo '<input type="text" name="option_description" value="' . htmlspecialchars($option->getDescription()) . '" style="min-width:120px;"> ';
                        echo '<input type="number" step="0.01" name="option_price" value="' . htmlspecialchars($option->getPriceModifier()) . '" style="width:80px;"> ';
                        echo '<label style="font-size:0.95em;"><input type="checkbox" name="option_default" value="1"' . ($option->isDefault() ? ' checked' : '') . '> Standard</label> ';
                        echo '<button type="submit" name="edit_option" class="btn btn-primary" style="margin-left:8px;">Option speichern</button>';
                        echo '</form>';
                        echo '</li>';
                    }
                    echo '</ul>';
                    // Option hinzufügen
                    echo '<form method="post" style="margin-top:8px;">';
                    echo '<input type="hidden" name="option_group_id" value="' . htmlspecialchars($group->getId()) . '">';
                    echo '<input type="hidden" name="article_id" value="' . htmlspecialchars($article->getId()) . '">';
                    echo '<input type="text" name="option_name" placeholder="Neue Option" style="min-width:120px;"> ';
                    echo '<input type="text" name="option_description" placeholder="Beschreibung" style="min-width:120px;"> ';
                    echo '<input type="number" step="0.01" name="option_price" placeholder="Preisaufschlag" style="width:80px;"> ';
                    echo '<label style="font-size:0.95em;"><input type="checkbox" name="option_default" value="1"> Standard</label> ';
                    echo '<button type="submit" name="add_option" class="btn btn-success" style="margin-left:8px;">Option hinzufügen</button>';
                    echo '</form>';
                    echo '</div>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        

        
        echo '</div>';
    }

}
ArticleManagement::main();
