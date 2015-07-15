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
    /** @PHPCRODM\Property(type="date", nullable=true) */
    public $publishDate;

    // untranslated:
    /** @PHPCRODM\Property(type="string", nullable=true) */
    public $author;

    /** @PHPCRODM\Property(type="string", translated=true) */
    public $topic;

    /** @PHPCRODM\Property(type="string", translated=true) */
    public $text;

    /** @PHPCRODM\Children() */
    public $children;

    /** @PHPCRODM\Child */
    public $child;

    /** @PHPCRODM\ReferenceMany() */
    public $relatedArticles = array();

    /** @PHPCRODM\Property(type="string", translated=true, nullable=true) */
    public $nullable;

    /** @PHPCRODM\Property(type="string", translated=true, nullable=true, property="custom-property-name") */
    public $customPropertyName;

    /** @PHPCRODM\Property(type="string", translated=true, assoc="", nullable=true)*/
    public $assoc;

    /**
     * @PHPCRODM\Property(type="string", assoc="", translated=true, nullable=true)
     */
    protected $settings;

    /**
     * @PHPCRODM\Property(type="string", assoc="", property="custom-settings", translated=true, nullable=true)
     */
    public $customNameSettings;

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
