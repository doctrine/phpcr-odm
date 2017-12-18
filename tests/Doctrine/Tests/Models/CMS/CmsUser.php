<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsUserRepository", referenceable=true)
 */
class CmsUser
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\Field(type="string", nullable=true) */
    public $status;
    /** @PHPCRODM\Field(type="string") */
    public $username;
    /** @PHPCRODM\Field(type="string", nullable=true) */
    public $name;
    /** @PHPCRODM\ReferenceOne(targetDocument="CmsAddress", cascade="persist") */
    public $address;
    /** @PHPCRODM\ReferenceMany(targetDocument="CmsArticle", cascade="persist") */
    public $articles;
    /** @PHPCRODM\ReferenceMany(targetDocument="CmsGroup") */
    public $groups;
    /** @PHPCRODM\ReferenceMany(targetDocument="CmsProfile") */
    public $profiles;
    /** @PHPCRODM\Children() */
    public $children;
    /** @PHPCRODM\Child(nodeName="assistant", cascade="persist") */
    public $child;
    /** @PHPCRODM\Referrers(referencedBy="user", referringDocument="Doctrine\Tests\Models\CMS\CmsArticle", cascade="persist") */
    public $articlesReferrers;

    public function __construct()
    {
        $this->articlesReferrers = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

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

    public function addGroup(CmsGroup $group)
    {
        $this->groups[] = $group;
        $group->addUser($this);
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function addProfile(CmsProfile $profile)
    {
        $this->profiles[] = $profile;
        $profile->setUser($this);
    }

    public function getProfiles()
    {
        return $this->profiles;
    }
}

class CmsUserRepository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id.
     *
     * @param object $document
     *
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/'.$document->username;
    }
}
