<?php

namespace Dionysosv2\Models;

class Article {
    private $id;
    private $plu;
    private ArticleCategory $category;
    private $name;
    private $price;
    private $description;
    private $nameEn;
    private $descriptionEn;

    public function __construct($id, $plu, $category, $name, $price, $description = "", $nameEn = "", $descriptionEn = "") {
        $this->id = $id;
        $this->plu = $plu;
        $this->category = $category;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->nameEn = $nameEn;
        $this->descriptionEn = $descriptionEn;
    }

    // Getter und Setter Methoden
    public function getId() {
        return $this->id;
    }

    public function getPlu() : string{
        return $this->plu;
    }

    public function getCategory(): ArticleCategory
    {
        return $this->category;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getNameEn() {
        return $this->nameEn ?? '';
    }

    public function getDescriptionEn() {
        return $this->descriptionEn ?? '';
    }

    // Methoden für sprachabhängige Ausgabe
    public function getLocalizedName($language = 'de') {
        if ($language === 'en' && !empty($this->nameEn)) {
            return $this->nameEn;
        }
        return $this->name;
    }

    public function getLocalizedDescription($language = 'de') {
        if ($language === 'en' && !empty($this->descriptionEn)) {
            return $this->descriptionEn;
        }
        return $this->description;
    }
}
