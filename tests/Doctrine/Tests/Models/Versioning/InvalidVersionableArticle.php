<?php

namespace Doctrine\Tests\Models\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(versionable: 'some_invalid_versioning_tpye')]
class InvalidVersionableArticle
{
    #[PHPCR\Id]
    public $id;

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
