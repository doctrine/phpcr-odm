<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsPageTranslatableRepository", referenceable=true, translator="attribute")
 */
class CmsPageTranslatable
{
    /**
     * @PHPCRODM\Id(strategy="repository")
     */
    public $id;

    /**
     * @PHPCRODM\Node
     */
    public $node;

    /**
     * @PHPCRODM\Locale
     */
    public $locale;

    /**
     * @PHPCRODM\String
     */
    public $content;

    /**
     * @PHPCRODM\String(translated=true)
     */
    public $title;

    /**
     * @PHPCRODM\MixedReferrers(referenceType="hard")
     */
    public $items = array();

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
    /**
     * Generate a document id
     *
     * @param object $document
     * @param null $parent
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/'.$document->title;
    }
}
