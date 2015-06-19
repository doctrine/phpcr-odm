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
    /** @PHPCRODM\Field(type="date", nullable=true) */
    public $publishDate;

    // untranslated:
    /** @PHPCRODM\Field(type="string", nullable=true) */
    public $author;

    /** @PHPCRODM\Field(type="string", translated=true) */
    public $topic;

    /** @PHPCRODM\Field(type="string", translated=true) */
    public $text;

    /** @PHPCRODM\Children() */
    public $children;

    /** @PHPCRODM\Child */
    public $child;

    /** @PHPCRODM\ReferenceMany() */
    public $relatedArticles = array();

    /** @PHPCRODM\Field(type="string", translated=true, nullable=true) */
    public $nullable;

    /** @PHPCRODM\Field(type="string", translated=true, nullable=true, property="custom-property-name") */
    public $customPropertyName;

    /** @PHPCRODM\Field(type="string", translated=true, assoc="", nullable=true)*/
    public $assoc;

    /**
     * @PHPCRODM\Field(type="string", assoc="", translated=true, nullable=true)
     */
    protected $settings;

    /**
     * @PHPCRODM\Field(type="string", assoc="", property="custom-settings", translated=true, nullable=true)
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
