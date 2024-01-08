<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(translator: 'attribute', referenceable: true)]
class Article
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Locale]
    public $locale = 'en';

    #[PHPCR\Node]
    public $node;

    #[PHPCR\Nodename]
    public $nodename;

    #[PHPCR\ParentDocument]
    public $parent;

    // untranslated:

    #[PHPCR\Field(type: 'date', nullable: true)]
    public $publishDate;

    // untranslated:

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $author;

    #[PHPCR\Field(type: 'string', translated: true)]
    public $topic;

    #[PHPCR\Field(type: 'string', translated: true)]
    public $text;

    #[PHPCR\Children]
    public $children;

    #[PHPCR\Child]
    public $child;

    #[PHPCR\ReferenceMany]
    public $relatedArticles = [];

    #[PHPCR\Field(type: 'string', nullable: true, translated: true)]
    public $nullable;

    #[PHPCR\Field(property: 'custom-property-name', type: 'string', nullable: true, translated: true)]
    public $customPropertyName;

    #[PHPCR\Field(type: 'string', assoc: '', nullable: true, translated: true)]
    public $assoc;

    #[PHPCR\Field(type: 'string', assoc: '', nullable: true, translated: true)]
    protected $settings;

    #[PHPCR\Field(property: 'custom-settings', type: 'string', assoc: '', nullable: true, translated: true)]
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
     * Sets the children.
     *
     * @param $children ArrayCollection
     */
    public function setChildren(ArrayCollection $children)
    {
        $this->children = $children;
    }

    /**
     * Set settings.
     */
    public function setSettings(array $settings = [])
    {
        $this->settings = $settings;
    }

    /**
     * Get settings.
     *
     * @return array $settings
     */
    public function getSettings()
    {
        return $this->settings;
    }
}
