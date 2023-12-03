<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class with no explicitly set properties for testing default values.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class DefaultMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;
}
