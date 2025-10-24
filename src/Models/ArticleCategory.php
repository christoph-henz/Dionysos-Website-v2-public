<?php

namespace Dionysosv2\Models;

class ArticleCategory
{
    public int $id;
    public string $code;
    public string $name;
    public string $nameEn;

    public function __construct(int $id, string $code, string $name, string $nameEn = "")
    {
        $this->id = $id;
        $this->code = $code;
        $this->name = $name;
        $this->nameEn = $nameEn;
    }

    // Getter-Methoden (optional, falls du Zugriff erzwingen willst)
    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameEn(): string
    {
        return $this->nameEn ?? '';
    }

    public function getLocalizedName($language = 'de'): string
    {
        if ($language === 'en' && !empty($this->nameEn)) {
            return $this->nameEn;
        }
        return $this->name;
    }
}
