<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(
 *     nodeType="phpcr:cms_profile",
 *     referenceable=true,
 *     repositoryClass="Doctrine\Tests\Models\CMS\CmsProfileRepository",
 *     uniqueNodeType=true
 * )
 */
class CmsProfile
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;

    /** @PHPCRODM\Uuid */
    public $uuid;

    /** @PHPCRODM\Field(type="string") */
    public $data;

    /** @PHPCRODM\ReferenceOne(targetDocument="CmsUser", cascade="persist") */
    public $user;

    public function getId()
    {
        return $this->id;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setUser(CmsUser $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}

class CmsProfileRepository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/' . $document->user->username . '/' . $document->data;
    }
}
