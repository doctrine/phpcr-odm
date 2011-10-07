<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\EmbeddedDocument
 */
class CmsAddress
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $country;
    /** @PHPCRODM\String */
    public $zip;
    /** @PHPCRODM\String */
    public $city;
    /** @PHPCRODM\String */
    public $street;

    public function getId()
    {
        return $this->id;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getZipCode()
    {
        return $this->zip;
    }

    public function getCity()
    {
        return $this->city;
    }
}
