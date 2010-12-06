<?php

namespace Doctrine\Tests\Models\ECommerce;

/**
 * ECommerceShipping
 * Represents a shipping method.
 *
 * @author Giorgio Sironi
 */
class ECommerceShipping
{
    /**
     */
    private $id;

    /**
     */
    private $days;

    public function getId()
    {
        return $this->id;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function setDays($days)
    {
        $this->days = $days;
    }
}
