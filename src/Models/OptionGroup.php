<?php

namespace Dionysosv2\Models;

class OptionGroup {
    private int $id;
    private string $name;
    private string $description;
    private bool $isRequired;
    private int $maxSelections;
    private int $minSelections;
    private array $options = [];

    public function __construct(
        int $id,
        string $name,
        string $description = '',
        bool $isRequired = false,
        int $maxSelections = 1,
        int $minSelections = 0
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->isRequired = $isRequired;
        $this->maxSelections = $maxSelections;
        $this->minSelections = $minSelections;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function isRequired(): bool {
        return $this->isRequired;
    }

    public function getMaxSelections(): int {
        return $this->maxSelections;
    }

    public function getMinSelections(): int {
        return $this->minSelections;
    }

    public function getOptions(): array {
        return $this->options;
    }

    public function addOption(Option $option): void {
        $this->options[] = $option;
    }

    public function setOptions(array $options): void {
        $this->options = $options;
    }
}
