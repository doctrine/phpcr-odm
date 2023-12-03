<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\Child as BaseChild;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Child extends BaseChild implements MappingAttribute
{
    public function __construct(
        string $nodeName = null,
        array $cascade = [],
    ) {
        $this->nodeName = $nodeName;
        $this->cascade = $cascade;
    }
}
