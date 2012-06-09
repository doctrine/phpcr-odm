<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains a mapped parent document via properties 
 * 
 * @PHPCRODM\Document
 */
class ParentDocumentMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
    
    /** @PHPCRODM\ParentDocument */
    public $parent;
}
