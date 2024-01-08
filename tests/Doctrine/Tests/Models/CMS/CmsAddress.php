<?php

namespace Documents;

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(repositoryClass: CmsAddressRepository::class, referenceable: true)]
class CmsAddress
{
    #[PHPCR\Id(strategy: 'repository')]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $country;

    #[PHPCR\Field(type: 'string')]
    public $zip;

    #[PHPCR\Field(type: 'string')]
    public $city;

    #[PHPCR\ReferenceOne(targetDocument: CmsUser::class)]
    public $user;

    #[PHPCR\Uuid]
    public $uuid;

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

class CmsAddressRepository extends DocumentRepository implements RepositoryIdInterface
{
    public function generateId(object $document, object $parent = null): string
    {
        return '/functional/'.$document->city.'_'.$document->zip;
    }
}
