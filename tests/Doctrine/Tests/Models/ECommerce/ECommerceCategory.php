<?php

namespace Doctrine\Tests\Models\ECommerce;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * ECommerceCategory
 * Represents a tag applied on particular products.
 *
 * @author Giorgio Sironi
 */
class ECommerceCategory
{
    /**
     */
    private $id;

    /**
     */
    private $name;

    /**
     */
    private $products;

    /**
     */
    private $children;

    /**
     */
    private $parent;

    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->children = new ArrayCollection();
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

    public function addProduct(ECommerceProduct $product)
    {
        if (!$this->products->contains($product)) {
            $this->products[] = $product;
            $product->addCategory($this);
        }
    }

    public function removeProduct(ECommerceProduct $product)
    {
        $removed = $this->products->removeElement($product);
        if ($removed) {
            $product->removeCategory($this);
        }
    }

    public function getProducts()
    {
        return $this->products;
    }

    private function setParent(ECommerceCategory $parent)
    {
        $this->parent = $parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function addChild(ECommerceCategory $child)
    {
        $this->children[] = $child;
        $child->setParent($this);
    }

    /** does not set the owning side. */
    public function brokenAddChild(ECommerceCategory $child)
    {
        $this->children[] = $child;
    }


    public function removeChild(ECommerceCategory $child)
    {
        $removed = $this->children->removeElement($child);
        if ($removed) {
            $child->removeParent();
        }
    }

    private function removeParent()
    {
        $this->parent = null;
    }
}
