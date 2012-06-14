<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped children via properties 
 * 
 * @PHPCRODM\Document
 */
class ChildMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
    
    /** @PHPCRODM\Child(name="first") */
    public $child1;
    
    /** @PHPCRODM\Child(name="second") */
    public $child2;
}
