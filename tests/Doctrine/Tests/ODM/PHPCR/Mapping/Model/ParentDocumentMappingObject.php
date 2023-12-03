<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that contains a mapped parent document via properties.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class ParentDocumentMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\ParentDocument */
    #[PHPCR\ParentDocument]
    public $parent;
}
