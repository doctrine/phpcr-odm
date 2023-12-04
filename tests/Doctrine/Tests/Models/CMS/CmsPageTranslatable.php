<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(repositoryClass: CmsPageTranslatableRepository::class, translator: 'attribute', referenceable: true)]
class CmsPageTranslatable
{
    #[PHPCR\Id(strategy: 'repository')]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Locale]
    public $locale;

    #[PHPCR\Field(type: 'string')]
    public $content;

    #[PHPCR\Field(type: 'string', translated: true)]
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

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getLocale()
    {
        return $this->locale;
    }
}

class CmsPageTranslatableRepository extends DocumentRepository implements RepositoryIdInterface
{
    public function generateId(object $document, object $parent = null): string
    {
        return '/functional/'.$document->title;
    }
}
