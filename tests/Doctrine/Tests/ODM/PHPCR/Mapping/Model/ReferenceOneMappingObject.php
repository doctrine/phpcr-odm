<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that references one other document.
 */
#[PHPCR\Document]
class ReferenceOneMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceOne(targetDocument: 'myDocument', strategy: 'weak')]
    public $referenceOneWeak;

    #[PHPCR\ReferenceOne(targetDocument: 'myDocument', strategy: 'hard')]
    public $referenceOneHard;
}
