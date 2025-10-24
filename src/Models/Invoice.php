<?php

namespace Dionysosv2\Models;

class Invoice {
    private $id;
    private $name;
    private $street;
    private $number;
    private $postalCode;
    private $city;
    private $email;
    private $phone;
    private $createdOn;
    public $orderItems = [];
    private $totalAmount;
    private $taxAmount;

    public function __construct(
        $id,
        $name,
        $street,
        $number,
        $postalCode,
        $city,
        $email,
        $phone,
        $createdOn = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->street = $street;
        $this->number = $number;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->email = $email;
        $this->phone = $phone;
        $this->createdOn = $createdOn ?: new \DateTime(); // Wenn kein Datum übergeben wird, aktuelles Datum verwenden

        $this->totalAmount = 0;
        $this->taxAmount = 0;
    }

    public static function createToGo($id, $name, $email, $phone, $createdOn = null): Invoice {
        return new self(
            $id,
            $name,
            '',     // street
            '',     // number
            '',     // postalCode
            '',     // city
            $email,
            $phone,
            $createdOn
        );
    }

    // Getter Methoden
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getStreet() {
        return $this->street;
    }

    public function getNumber() {
        return $this->number;
    }

    public function getPostalCode() {
        return $this->postalCode;
    }

    public function getCity() {
        return $this->city;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getPhone() {
        return $this->phone;
    }

    public function getCreatedOn() {
        return $this->createdOn;
    }

    public function getOrderItems() {
        return $this->orderItems;
    }

    public function getTotalAmount() {
        return $this->totalAmount;
    }

    public function getTaxAmount() {
        return $this->taxAmount;
    }


    // Gesamtbetrag und Steuer neu berechnen
    private function recalculateInvoice() {
        $this->totalAmount = 0;
        foreach ($this->orderItems as $item) {
            $this->totalAmount += $item->getTotalPrice();
        }

        // Steuer (USt) berechnen (7% von totalAmount)
        $this->taxAmount = $this->totalAmount * 0.07;
    }

    // Gesamtrechnung (Bruttobetrag = TotalAmount + USt)
    public function getGrossAmount() {
        return $this->totalAmount;
    }
}