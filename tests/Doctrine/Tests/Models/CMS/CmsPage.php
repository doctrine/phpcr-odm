<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsPageRepository", referenceable=true)
 */
class CmsPage
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;

    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\Field(type="string") */
    public $content;

    /** @PHPCRODM\Field(type="string") */
    public $title;

    /** @PHPCRODM\MixedReferrers(referenceType="hard") */
    public $items = [];

    public function getId()
    {
        return $this->id;
    }

    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function addItem($item)
    {
        $this->items[] = $item;
    }

    public function removeItem($item)
    {
        foreach ($this->items as $key => $value) {
            if ($value === $item) {
                unset($this->items[$key]);
            }
        }
    }
}

class CmsPageRepository extends DocumentRepository implements RepositoryIdInterface
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
        return '/functional/'.$document->title;
    }
}
