<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that references many other documents.
 *
 * @PHPCRODM\Document
 */
#[PHPCR\Document]
class ReferenceManyMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\ReferenceMany(targetDocument="myDocument", strategy="weak") */
    #[PHPCR\ReferenceMany(targetDocument: 'myDocument', strategy: 'weak')]
    public $referenceManyWeak;

    /** @PHPCRODM\ReferenceMany(targetDocument="myDocument", strategy="hard") */
    #[PHPCR\ReferenceMany(targetDocument: 'myDocument', strategy: 'hard')]
    public $referenceManyHard;
}
