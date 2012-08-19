<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that references one other document
 * 
 * @PHPCRODM\Document
 */
class ReferenceOneMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
    
    /** @PHPCRODM\ReferenceOne(targetDocument="myDocument", strategy="weak") */
    public $referenceOneWeak;
    
    /** @PHPCRODM\ReferenceOne(targetDocument="myDocument", strategy="hard") */
    public $referenceOneHard;
}
