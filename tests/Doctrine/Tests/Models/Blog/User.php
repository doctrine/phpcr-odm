<?php

namespace Documents;

namespace Doctrine\Tests\Models\Blog;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class User
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $username;

    #[PHPCR\Field(type: 'string')]
    public $name;

    #[PHPCR\Field(type: 'string')]
    public $status;

    #[PHPCR\Field(type: 'long', nullable: true)]
    public $age;
}
