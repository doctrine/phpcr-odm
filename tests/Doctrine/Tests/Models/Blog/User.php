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

    /** @PHPCRODM\String */
    public $username;

    /** @PHPCRODM\String */
    public $name;

    /** @PHPCRODM\String */
    public $status;
}

