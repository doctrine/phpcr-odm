<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(repositoryClass: CmsPageRepository::class, referenceable: true)]
class CmsPage
{
    #[PHPCR\Id(strategy: 'repository')]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Field(type: 'string')]
    public $content;

    #[PHPCR\Field(type: 'string')]
    public $title;

    #[PHPCR\MixedReferrers(referenceType: 'hard')]
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
