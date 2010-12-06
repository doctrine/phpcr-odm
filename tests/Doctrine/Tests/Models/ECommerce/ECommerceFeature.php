<?php

namespace Doctrine\Tests\Models\ECommerce;

/**
 * Describes a product feature.
 *
 * @author Giorgio Sironi
 */
class ECommerceFeature
{
    /**
     */
    private $id;

    /**
     */
    private $description;

    /**
     */
    private $product;

    public function getId() {
        return $this->id;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function setProduct(ECommerceProduct $product) {
        $this->product = $product;
    }

    public function removeProduct() {
        if ($this->product !== null) {
            $product = $this->product;
            $this->product = null;
            $product->removeFeature($this);
        }
    }

    public function getProduct() {
        return $this->product;
    }
}
