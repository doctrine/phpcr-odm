<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(versionable: 'full')]
class ExtendedVersionableArticle extends FullVersionableArticle
{
    #[PHPCR\Field(type: 'string')]
    public $author;

    #[PHPCR\Field(type: 'string')]
    public $topic;

    #[PHPCR\Field(type: 'string')]
    private $text;

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }
}
