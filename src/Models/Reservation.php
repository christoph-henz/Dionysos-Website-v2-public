<?php

namespace Dionysosv2\Models;

class Reservation {
    private $id;
    private $customerName;
    private $customerEmail;
    private $customerPhone;
    private $numberOfPeople;
    private $reservationDate;
    private $reservationTime;
    private $CreatedOn;

    public function __construct($id, $customerName, $customerEmail, $customerPhone, $numberOfPeople, $reservationDate, $reservationTime) {
        $this->id = $id;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->customerPhone = $customerPhone;
        $this->numberOfPeople = $numberOfPeople;
        $this->reservationDate = $reservationDate;
        $this->reservationTime = $reservationTime;
        $this->CreatedOn = new \DateTime('now');
    }

    // Getter und Setter Methoden
    public function getId() {
        return $this->id;
    }

    public function getCustomerName() {
        return $this->customerName;
    }

    public function getCustomerEmail() {
        return $this->customerEmail;
    }

    public function getCustomerPhone() {
        return $this->customerPhone;
    }

    public function getNumberOfPeople() {
        return $this->numberOfPeople;
    }

    public function getReservationDate() {
        return $this->reservationDate;
    }

    public function getReservationTime() {
        return $this->reservationTime;
    }
}
