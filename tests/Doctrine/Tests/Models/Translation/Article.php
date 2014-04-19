<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @PHPCRODM\Document(translator="attribute", referenceable=true)
 */
class Article
{
    /** @PHPCRODM\Id */
    public $id;

    /**
     * @PHPCRODM\Locale
     */
    public $locale = 'en';

    /** @PHPCRODM\Node */
    public $node;

    /** @PHPCRODM\Nodename */
    public $nodename;

    /**
     * @PHPCRODM\ParentDocument */
    public $parent;

    // untranslated:
    /** @PHPCRODM\Date(nullable=true) */
    public $publishDate;

    // untranslated:
    /** @PHPCRODM\String(nullable=true) */
    public $author;

    /** @PHPCRODM\String(translated=true) */
    public $topic;

    /** @PHPCRODM\String(translated=true) */
    public $text;

    /** @PHPCRODM\Children() */
    public $children;

    /** @PHPCRODM\Child */
    public $child;

    /** @PHPCRODM\ReferenceMany() */
    public $relatedArticles = array();

    /** @PHPCRODM\String(translated=true, nullable=true) */
    public $nullable;

    /** @PHPCRODM\String(translated=true, assoc="", nullable=true)*/
    public $assoc;

    /**
     * @PHPCRODM\String(assoc="", translated=true, nullable=true)
     */
    protected $settings;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getText()
    {
        return $this->text;
    }
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets the children
     *
     * @param $children ArrayCollection
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;
    }

    /**
     * Set settings
     *
     * @param array $settings
     */
    public function setSettings(array $settings = array())
    {
        $this->settings = $settings;
    }

    /**
     * Get settings
     *
     * @return array $settings
     */
    public function getSettings()
    {
        return $this->settings;
    }
}
