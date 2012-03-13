<?php

namespace Documents;

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
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

    /** @PHPCRODM\ReferenceOne(targetDocument="CmsUser") */
    public $user;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
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

    public function setUser(CmsUser $user)
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}
