<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(translator: 'attribute')]
class Comment
{
    #[PHPCR\Id(strategy: 'parent')]
    public $id;

    #[PHPCR\Nodename]
    public $name;

    #[PHPCR\Locale]
    public $locale = 'en';

    #[PHPCR\ParentDocument]
    public $parent;

    #[PHPCR\Field(type: 'string', nullable: true, translated: true)]
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
