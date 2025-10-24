<?php

namespace Dionysosv2\Controller;
use Dionysosv2\Models\ArticleCategory;
use Dionysosv2\Views\Page;

class ArticleCategoryController extends Page
{
    public function __construct()
    {
        parent::__construct(); // initialisiert $_database (SQLite oder MySQL je nach Umgebung)
    }

    /**
     * Gibt alle Kategorien aus der Tabelle zurück
     */
    public function getAllCategories(): array
    {
        $stmt = $this->_database->prepare("SELECT * FROM article_category ORDER BY label");
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $categories = [];
        foreach ($rows as $row) {
            $categories[] = new \Dionysosv2\Models\ArticleCategory(
                (int)$row['id'],
                (string)$row['code'],
                (string)$row['label'],
                isset($row['label_en']) ? (string)$row['label_en'] : ''
            );
        }
        return $categories;
    }

    private function getCategoryById(int $id): ArticleCategory
    {
        $stmt = $this->_database->prepare("SELECT * FROM article_category WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            throw new \Exception("Category not found for ID $id");
        }
        return new ArticleCategory(
            (int)$row['id'],
            (string)$row['code'],
            (string)$row['label'],
            isset($row['label_en']) ? (string)$row['label_en'] : ''
        );
    }

    /**
     * Fügt eine neue Kategorie hinzu (z. B. für Backend-Admin)
     */
    public function addCategory(string $code, string $label): bool
    {
        $stmt = $this->_database->prepare("INSERT INTO article_category (code, label) VALUES (:code, :label)");
        return $stmt->execute(['code' => $code, 'label' => $label]);
    }

    /**
     * Löscht eine Kategorie anhand ihres Codes
     */
    public function deleteCategory(string $code): bool
    {
        $stmt = $this->_database->prepare("DELETE FROM article_category WHERE code = :code");
        return $stmt->execute(['code' => $code]);
    }
}
