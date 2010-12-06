<?php

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * ECommerceCart
 * Represents a typical cart of a shopping application.
 *
 * @author Giorgio Sironi
 */
class ECommerceCart
{
    /**
     */
    private $id;

    /**
     */
    private $payment;

    /**
     */
    private $customer;

    /**
     */
    private $products;

    public function __construct()
    {
        $this->products = new ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getPayment() {
        return $this->payment;
    }

    public function setPayment($payment) {
        $this->payment = $payment;
    }

    public function setCustomer(ECommerceCustomer $customer) {
        if ($this->customer !== $customer) {
            $this->customer = $customer;
            $customer->setCart($this);
        }
    }

    public function removeCustomer() {
        if ($this->customer !== null) {
            $customer = $this->customer;
            $this->customer = null;
            $customer->removeCart();
        }
    }

    public function getCustomer() {
        return $this->customer;
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function addProduct(ECommerceProduct $product) {
        $this->products[] = $product;
    }

    public function removeProduct(ECommerceProduct $product) {
        return $this->products->removeElement($product);
    }
}
