<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsUserRepository", referenceable=true)
 */
class CmsUser
{
    /** @PHPCRODM\Id(strategy="repository") */
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

class CmsUserRepository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document)
    {
        return '/functional/'.$document->username;
    }
}
