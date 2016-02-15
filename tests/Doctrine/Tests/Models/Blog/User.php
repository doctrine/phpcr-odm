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

    /** @PHPCRODM\Field(type="string") */
    public $username;

    /** @PHPCRODM\Field(type="string") */
    public $name;

    /** @PHPCRODM\Field(type="string") */
    public $status;

    /** @PHPCRODM\Field(type="long", nullable=true) */
    public $age;
}
