<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that references many other documents.
 */
#[PHPCR\Document]
class ReferenceManyMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceMany(targetDocument: 'myDocument', strategy: 'weak')]
    public $referenceManyWeak;

    #[PHPCR\ReferenceMany(targetDocument: 'myDocument', strategy: 'hard')]
    public $referenceManyHard;
}
