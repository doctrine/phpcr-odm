<?php

namespace Documents;

namespace Doctrine\Tests\Models\Blog;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document()
 */
class User
{
    /** @PHPCRODM\Id() */
    public $id;

    /** @PHPCRODM\Property(type="string") */
    public $username;

    /** @PHPCRODM\Property(type="string") */
    public $name;

    /** @PHPCRODM\Property(type="string") */
    public $status;
}

