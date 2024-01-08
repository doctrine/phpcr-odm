<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class with no explicitly set properties for testing default values.
 */
#[PHPCR\Document]
class DefaultMappingObject
{
    #[PHPCR\Id]
    public $id;
}
