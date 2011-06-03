<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class CmsAddress
{
    /** @ODM\Id */
    public $id;
    /** @ODM\String */
    public $country;
    /** @ODM\String */
    public $zip;
    /** @ODM\String */
    public $city;
    /** @ODM\String */
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