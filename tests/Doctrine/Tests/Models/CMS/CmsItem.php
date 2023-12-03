<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(repositoryClass: CmsItemRepository::class, referenceable: true)]
class CmsItem
{
    #[PHPCR\Id(strategy: 'repository')]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Field(type: 'string')]
    public $name;

    #[PHPCR\ReferenceOne(strategy: 'hard', cascade: 'persist')]
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
     *
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/'.$document->name;
    }
}
