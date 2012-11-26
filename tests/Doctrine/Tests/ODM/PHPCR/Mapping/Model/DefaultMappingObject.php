<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class with no explicitly set properties for testing default values.
 * @PHPCRODM\Document
 */
class DefaultMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
}


