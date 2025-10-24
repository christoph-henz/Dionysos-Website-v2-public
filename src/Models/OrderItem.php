<?php

namespace Dionysosv2\Models;

class OrderItem {
    private $article;  // Artikel, der zu diesem OrderItem gehört
    private $quantity; // Menge des Artikels
    private $totalPrice; // Gesamtpreis für diesen Artikel (Menge * Preis)

    public function __construct(Article $article, $quantity = 1) {
        $this->article = $article;
        $this->quantity = $quantity;
        $this->updateTotalPrice();
    }

    // Gesamtpreis neu berechnen
    private function updateTotalPrice() {
        $this->totalPrice = $this->article->getPrice() * $this->quantity;
    }

    // Getter Methoden
    public function getArticle() {
        return $this->article;
    }

    public function getQuantity() {
        return $this->quantity;
    }

    public function getTotalPrice() {
        return $this->totalPrice;
    }

    // Methode, um die Menge zu erhöhen
    public function addQuantity($quantity) {
        if ($quantity > 0) {
            $this->quantity += $quantity;
            $this->updateTotalPrice(); // Gesamtpreis aktualisieren
        }
    }

    // Methode, um die Menge zu verringern
    public function subtractQuantity($quantity) {
        if ($quantity > 0 && $this->quantity - $quantity >= 0) {
            $this->quantity -= $quantity;
            $this->updateTotalPrice(); // Gesamtpreis aktualisieren
        }
    }
}