<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that contains mapped fields via properties 
 * 
 * @PHPCRODM\Document
 */
class NodenameMappingObject
{
    /** @PHPCRODM\Id */
    public $id;
    
    /** @PHPCRODM\Nodename */
    public $namefield;
}
