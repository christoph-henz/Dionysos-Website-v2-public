<?php

namespace Dionysosv2\Models;

class Option {
    private int $id;
    private int $optionGroupId;
    private string $name;
    private string $description;
    private float $priceModifier;
    private bool $isDefault;

    public function __construct(
        int $id,
        int $optionGroupId,
        string $name,
        string $description = '',
        float $priceModifier = 0.0,
        bool $isDefault = false
    ) {
        $this->id = $id;
        $this->optionGroupId = $optionGroupId;
        $this->name = $name;
        $this->description = $description;
        $this->priceModifier = $priceModifier;
        $this->isDefault = $isDefault;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getOptionGroupId(): int {
        return $this->optionGroupId;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getPriceModifier(): float {
        return $this->priceModifier;
    }

    public function isDefault(): bool {
        return $this->isDefault;
    }
}
