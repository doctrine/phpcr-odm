<?php

namespace Documents;

namespace Doctrine\Tests\Models\Blog;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document()
 */
class Post
{
    /** @PHPCRODM\Id() */
    public $id;

    /** @PHPCRODM\Property(type="string") */
    public $title;

    /** @PHPCRODM\Property(type="string") */
    public $username;
}
