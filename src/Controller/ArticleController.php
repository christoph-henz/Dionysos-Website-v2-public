<?php

namespace Dionysosv2\Controller;

use Dionysosv2\Models\Article;
use Dionysosv2\Models\ArticleCategory;
use Dionysosv2\Views\Page;

class ArticleController extends Page{
    /**
     * Fügt einen neuen Artikel hinzu.
     */
    public function addArticle($plu, $categoryId, $name, $price, $description = '', $nameEn = '', $descriptionEn = ''): bool {
        $tableName = $this->isLocal ? "article" : "article";
        $stmt = $this->_database->prepare("INSERT INTO {$tableName} (plu, category_id, name, price, description, name_en, description_en) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$plu, $categoryId, $name, $price, $description, $nameEn, $descriptionEn]);
    }

    /**
     * Löscht einen Artikel anhand der ID.
     */
    public function deleteArticle($id): bool {
        $tableName = $this->isLocal ? "article" : "article";
        $stmt = $this->_database->prepare("DELETE FROM {$tableName} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Aktualisiert einen bestehenden Artikel.
     */
    public function updateArticle($id, $plu, $categoryId, $name, $price, $description = '', $nameEn = '', $descriptionEn = ''): bool {
        $tableName = $this->isLocal ? "article" : "article";
        $stmt = $this->_database->prepare("UPDATE {$tableName} SET plu = ?, category_id = ?, name = ?, price = ?, description = ?, name_en = ?, description_en = ? WHERE id = ?");
        return $stmt->execute([$plu, $categoryId, $name, $price, $description, $nameEn, $descriptionEn, $id]);
    }

    public function __construct() {
        parent::__construct();
    }

    /**
     * Gibt alle Artikel zurück.
     *
     * @return Article[]
     */
    public function getAllArticles(): array {
        $articles = [];
        $tableName = $this->isLocal ? "article" : "article"; // Gleicher Tabellenname in beiden DBs

        $stmt = $this->_database->prepare("SELECT * FROM {$tableName}");
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($result as $row) {
            $articles[] = $this->mapRowToArticle($row);
        }

        return $articles;
    }

    /**
     * Gibt alle Artikel einer bestimmten Kategorie zurück.
     *
     * @param ArticleCategory $category
     * @return Article[]
     */
    public function getAllArticlesByCategory(ArticleCategory $category): array {
        $articles = [];
        $tableName = $this->isLocal ? "article" : "article"; // Gleicher Tabellenname in beiden DBs

        $stmt = $this->_database->prepare("SELECT * FROM {$tableName} WHERE category = :category");
        $stmt->bindValue(':category', $category->name);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($result as $row) {
            $articles[] = $this->mapRowToArticle($row);
        }

        return $articles;
    }

    /**
     * Gibt einen Artikel anhand der ID zurück.
     *
     * @param int $id
     * @return Article|null
     */
    public function getArticleById(int $id): ?Article {
        $tableName = $this->isLocal ? "article" : "article"; // Gleicher Tabellenname in beiden DBs
        
        $stmt = $this->_database->prepare("SELECT * FROM {$tableName} WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToArticle($row) : null;
    }

    /**
     * Gibt einen Artikel anhand der PLU zurück.
     *
     * @param string $plu
     * @return Article|null
     */
    public function getArticleByPlu(string $plu): ?Article {
        $tableName = $this->isLocal ? "article" : "article"; // Gleicher Tabellenname in beiden DBs
        
        $stmt = $this->_database->prepare("SELECT * FROM {$tableName} WHERE plu = :plu");
        $stmt->bindValue(':plu', $plu, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToArticle($row) : null;
    }


    private function getCategoryById(int $id): ArticleCategory {
        $tableName = $this->isLocal ? "article_category" : "article_category"; // Gleicher Tabellenname in beiden DBs
        
        $stmt = $this->_database->prepare("SELECT * FROM {$tableName} WHERE id = :id");
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \Exception("Category not found for ID $id");
        }

        return new ArticleCategory(
            (int)$row['id'],
            (string)$row['code'],
            (string)$row['label']
        );
    }

    /**
     * Hilfsmethode zur Umwandlung eines Datenbank-Eintrags in ein Article-Objekt.
     *
     * @param array $row
     * @return Article
     */
    private function mapRowToArticle(array $row): Article {
        $category = $this->getCategoryById((int)$row['category_id']);

        return new Article(
            (int)$row['id'],
            (string)$row['plu'],
            $category,
            (string)$row['name'],
            (float)$row['price'],
            $row['description'] ?? '',
            $row['name_en'] ?? '',
            $row['description_en'] ?? ''
        );
    }
}
