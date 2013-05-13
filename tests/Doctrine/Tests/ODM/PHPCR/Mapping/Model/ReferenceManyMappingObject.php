<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that references many other documents
 *
 * @PHPCRODM\Document
 */
class ReferenceManyMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\ReferenceMany(targetDocument="myDocument", strategy="weak") */
    public $referenceManyWeak;

    /** @PHPCRODM\ReferenceMany(targetDocument="myDocument", strategy="hard") */
    public $referenceManyHard;
}
