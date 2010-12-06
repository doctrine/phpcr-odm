<?php

namespace Doctrine\Tests\Models\CMS;

/**
 * @EmbeddedDocument
 */
class CmsAddress
{
    /** @Id */
    public $id;
    /** @String */
    public $country;
    /** @String */
    public $zip;
    /** @String */
    public $city;
    /** @String */
    public $street;

    public function getId() {
        return $this->id;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getZipCode() {
        return $this->zip;
    }

    public function getCity() {
        return $this->city;
    }
}