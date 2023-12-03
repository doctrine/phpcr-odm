<?php

namespace Documents;

namespace Doctrine\Tests\Models\Blog;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * Comment will be a child of the Post in the test scenario
 *
 * Used for Join tests
 */
#[PHPCR\Document]
class Comment
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $title;
}
