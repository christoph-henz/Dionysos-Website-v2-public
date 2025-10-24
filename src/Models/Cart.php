<?php
namespace Dionysosv2\Models;

class Cart {
    private array $items = [];
    private float $total = 0.0;

    public function addItem(Article $article, int $quantity = 1): void {
        $id = $article->getId();
        if (!isset($this->items[$id])) {
            $this->items[$id] = [
                'article' => $article,
                'quantity' => 0
            ];
        }
        $this->items[$id]['quantity'] += $quantity;
        $this->calculateTotal();
    }

    public function removeItem(int $id): void {
        if (isset($this->items[$id])) {
            unset($this->items[$id]);
            $this->calculateTotal();
        }
    }

    public function updateQuantity(int $id, int $delta): void {
        if (isset($this->items[$id])) {
            $newQuantity = $this->items[$id]['quantity'] + $delta;
            
            if ($newQuantity <= 0) {
                $this->removeItem($id);
            } else {
                $this->items[$id]['quantity'] = $newQuantity;
                $this->calculateTotal();
            }
        }
    }

    private function calculateTotal(): void {
        $this->total = 0.0;
        foreach ($this->items as $item) {
            if (isset($item['total'])) {
                // Item has precalculated total (with options)
                $this->total += $item['total'];
            } else {
                // Standard item without options
                $this->total += $item['article']->getPrice() * $item['quantity'];
            }
        }
    }

    public function getTotal(): float {
        return $this->total;
    }

    public function addItemWithOptions(Article $article, int $quantity = 1, array $options = [], string $cartKey = null): void {
        $key = $cartKey ?? $article->getId();
        
        if (!isset($this->items[$key])) {
            // Calculate total price including options
            $optionsPriceModifier = 0;
            foreach ($options as $option) {
                $optionsPriceModifier += $option['price'];
            }
            
            $this->items[$key] = [
                'article' => $article,
                'quantity' => 0,
                'options' => $options,
                'total' => 0
            ];
        }
        
        $this->items[$key]['quantity'] += $quantity;
        
        // Recalculate total for this item
        $basePrice = $this->items[$key]['article']->getPrice();
        $optionsPriceModifier = 0;
        if (isset($this->items[$key]['options'])) {
            foreach ($this->items[$key]['options'] as $option) {
                $optionsPriceModifier += $option['price'];
            }
        }
        $this->items[$key]['total'] = ($basePrice + $optionsPriceModifier) * $this->items[$key]['quantity'];
        
        $this->calculateTotal();
    }

    public function updateQuantityByKey(string $key, int $delta): void {
        if (isset($this->items[$key])) {
            $newQuantity = $this->items[$key]['quantity'] + $delta;
            
            if ($newQuantity <= 0) {
                unset($this->items[$key]);
            } else {
                $this->items[$key]['quantity'] = $newQuantity;
                
                // Recalculate total for this item
                $basePrice = $this->items[$key]['article']->getPrice();
                $optionsPriceModifier = 0;
                if (isset($this->items[$key]['options'])) {
                    foreach ($this->items[$key]['options'] as $option) {
                        $optionsPriceModifier += $option['price'];
                    }
                }
                $this->items[$key]['total'] = ($basePrice + $optionsPriceModifier) * $this->items[$key]['quantity'];
            }
            
            $this->calculateTotal();
        }
    }

    public function getItems(): array {
        return $this->items;
    }
}