<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that references one other document.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class ReferenceOneMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\ReferenceOne(targetDocument="myDocument", strategy="weak") */
    #[PHPCR\ReferenceOne(targetDocument: 'myDocument', strategy: 'weak')]
    public $referenceOneWeak;

    /** @PHPCRODM\ReferenceOne(targetDocument="myDocument", strategy="hard") */
    #[PHPCR\ReferenceOne(targetDocument: 'myDocument', strategy: 'hard')]
    public $referenceOneHard;
}
