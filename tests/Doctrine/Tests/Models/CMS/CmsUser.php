<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class CmsUser
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String(name="status") */
    public $status;
    /** @PHPCRODM\String(name="username") */
    public $username;
    /** @PHPCRODM\String(name="name") */
    public $name;
    /** @PHPCRODM\ReferenceOne(targetDocument="CmsAddress") */
    public $address;

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAddress(CmsAddress $address)
    {
        $this->address = $address;
        $address->setUser($this);
    }

    public function getAddress()
    {
        return $this->address;
    }
}
