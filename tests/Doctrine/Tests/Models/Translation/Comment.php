<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class Comment
{
    /** @PHPCRODM\Id(strategy="parent") */
    public $id;

    /**
     * @PHPCRODM\Nodename()
     */
    public $name;

    /**
     * @PHPCRODM\Locale
     */
    public $locale = 'en';

    /** @PHPCRODM\ParentDocument */
    public $parent;

    /** @PHPCRODM\String(translated=true,nullable=true) */
    private $text;

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }
}