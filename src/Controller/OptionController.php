<?php

namespace Dionysosv2\Controller;

use Dionysosv2\Models\Option;
use Dionysosv2\Models\OptionGroup;
use Dionysosv2\Views\Page;

class OptionController extends Page {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Gibt alle Optionsgruppen für einen bestimmten Artikel zurück.
     *
     * @param int $articleId
     * @return OptionGroup[]
     */
    public function getOptionGroupsByArticleId(int $articleId): array {
        $optionGroups = [];

        $stmt = $this->_database->prepare("
            SELECT og.*, aog.display_order 
            FROM option_groups og
            INNER JOIN article_option_groups aog ON og.id = aog.option_group_id
            WHERE aog.article_id = :article_id
            ORDER BY aog.display_order, og.name
        ");
        $stmt->bindParam(':article_id', $articleId, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $optionGroup = $this->mapRowToOptionGroup($row);
            $optionGroup->setOptions($this->getOptionsByGroupId($optionGroup->getId()));
            $optionGroups[] = $optionGroup;
        }

        return $optionGroups;
    }

    /**
     * Gibt alle Optionen für eine bestimmte Optionsgruppe zurück.
     *
     * @param int $optionGroupId
     * @return Option[]
     */
    public function getOptionsByGroupId(int $optionGroupId): array {
        $options = [];

        $stmt = $this->_database->prepare("
            SELECT * FROM options 
            WHERE option_group_id = :option_group_id 
            ORDER BY is_default DESC, name
        ");
        $stmt->bindParam(':option_group_id', $optionGroupId, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $row) {
            $options[] = $this->mapRowToOption($row);
        }

        return $options;
    }

    /**
     * Gibt eine einzelne Option anhand ihrer ID zurück.
     *
     * @param int $optionId
     * @return Option|null
     */
    public function getOptionById(int $optionId): ?Option {
        $stmt = $this->_database->prepare("SELECT * FROM options WHERE id = :option_id");
        $stmt->bindParam(':option_id', $optionId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->mapRowToOption($row) : null;
    }

    /**
     * Verknüpft eine Optionsgruppe mit einem Artikel.
     *
     * @param int $articleId
     * @param int $optionGroupId
     * @param int $displayOrder
     * @return bool
     */
    public function linkOptionGroupToArticle(int $articleId, int $optionGroupId, int $displayOrder = 0): bool {
        try {
            // Prüfen ob wir mit SQLite oder MySQL arbeiten
            $driver = $this->_database->getAttribute(\PDO::ATTR_DRIVER_NAME);
            
            if ($driver === 'sqlite') {
                $stmt = $this->_database->prepare("
                    INSERT OR IGNORE INTO article_option_groups 
                    (article_id, option_group_id, display_order) 
                    VALUES (:article_id, :option_group_id, :display_order)
                ");
            } else {
                $stmt = $this->_database->prepare("
                    INSERT IGNORE INTO article_option_groups 
                    (article_id, option_group_id, display_order) 
                    VALUES (:article_id, :option_group_id, :display_order)
                ");
            }
            
            $stmt->bindParam(':article_id', $articleId, \PDO::PARAM_INT);
            $stmt->bindParam(':option_group_id', $optionGroupId, \PDO::PARAM_INT);
            $stmt->bindParam(':display_order', $displayOrder, \PDO::PARAM_INT);
            $stmt->execute();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Hilfsmethode zur Umwandlung eines Datenbank-Eintrags in ein OptionGroup-Objekt.
     *
     * @param array $row
     * @return OptionGroup
     */
    private function mapRowToOptionGroup(array $row): OptionGroup {
        return new OptionGroup(
            (int)$row['id'],
            (string)$row['name'],
            (string)($row['description'] ?? ''),
            (bool)$row['is_required'],
            (int)$row['max_selections'],
            (int)$row['min_selections']
        );
    }

    /**
     * Hilfsmethode zur Umwandlung eines Datenbank-Eintrags in ein Option-Objekt.
     *
     * @param array $row
     * @return Option
     */
    private function mapRowToOption(array $row): Option {
        return new Option(
            (int)$row['id'],
            (int)$row['option_group_id'],
            (string)$row['name'],
            (string)($row['description'] ?? ''),
            (float)$row['price_modifier'],
            (bool)$row['is_default']
        );
    }
}
