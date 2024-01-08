<?php

namespace Documents;

namespace Doctrine\Tests\Models\Blog;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class Post
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $title;

    #[PHPCR\Field(type: 'string')]
    public $username;
}
