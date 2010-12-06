<?php

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * ECommerceProduct
 * Represents a type of product of a shopping application.
 *
 * @author Giorgio Sironi
 */
class ECommerceProduct
{
    /**
     */
    private $id;

    /**
     */
    private $name;

    /**
     */
    private $shipping;

    /**
     */
    private $features;

    /**
     */
    private $categories;

    /**
     */
    private $related;

    public function __construct()
    {
        $this->features = new ArrayCollection;
        $this->categories = new ArrayCollection;
        $this->related = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getShipping()
    {
        return $this->shipping;
    }

    public function setShipping(ECommerceShipping $shipping)
    {
        $this->shipping = $shipping;
    }

    public function removeShipping()
    {
        $this->shipping = null;
    }

    public function getFeatures()
    {
        return $this->features;
    }

    public function addFeature(ECommerceFeature $feature)
    {
        $this->features[] = $feature;
        $feature->setProduct($this);
    }

    /** does not set the owning side */
    public function brokenAddFeature(ECommerceFeature $feature)
    {
        $this->features[] = $feature;
    }

    public function removeFeature(ECommerceFeature $feature)
    {
        $removed = $this->features->removeElement($feature);
        if ($removed) {
            $feature->removeProduct();
        }
        return $removed;
    }

    public function addCategory(ECommerceCategory $category)
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->addProduct($this);
        }
    }

    public function removeCategory(ECommerceCategory $category)
    {
        $removed = $this->categories->removeElement($category);
        if ($removed) {
            $category->removeProduct($this);
        }
    }

    public function getCategories()
    {
        return $this->categories;
    }

    public function getRelated()
    {
        return $this->related;
    }

    public function addRelated(ECommerceProduct $related)
    {
        if (!$this->related->contains($related)) {
            $this->related[] = $related;
            $related->addRelated($this);
        }
    }

    public function removeRelated(ECommerceProduct $related)
    {
        $removed = $this->related->removeElement($related);
        if ($removed) {
            $related->removeRelated($this);
        }
    }
}
