<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsItemRepository", referenceable=true)
 */
class CmsItem
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String */
    public $name;
    /** @PHPCRODM\ReferenceOne(strategy="hard", cascade="persist") */
    public $documentTarget;


    public function getId()
    {
        return $this->id;
    }

    public function setDocumentTarget($documentTarget)
    {
        $this->documentTarget = $documentTarget;
        return $this;
    }

    public function getDocumentTarget()
    {
        return $this->documentTarget;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
}

class CmsItemRepository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/'.$document->name;
    }
}
