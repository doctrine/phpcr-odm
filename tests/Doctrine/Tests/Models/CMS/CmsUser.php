<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(repositoryClass: CmsUserRepository::class, referenceable: true)]
class CmsUser
{
    #[PHPCR\Id(strategy: 'repository')]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $status;

    #[PHPCR\Field(type: 'string')]
    public $username;

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $name;

    #[PHPCR\ReferenceOne(targetDocument: CmsAddress::class, cascade: 'persist')]
    public $address;

    #[PHPCR\ReferenceMany(targetDocument: CmsArticle::class, cascade: 'persist')]
    public $articles;

    #[PHPCR\ReferenceMany(targetDocument: CmsGroup::class)]
    public $groups;

    #[PHPCR\ReferenceMany(targetDocument: CmsProfile::class)]
    public $profiles;

    #[PHPCR\Children]
    public $children;

    #[PHPCR\Child(nodeName: 'assistant', cascade: 'persist')]
    public $child;

    #[PHPCR\Referrers(referencedBy: 'user', referringDocument: CmsArticle::class, cascade: 'persist')]
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
    public function generateId(object $document, object $parent = null): string
    {
        return '/functional/'.$document->username;
    }
}
