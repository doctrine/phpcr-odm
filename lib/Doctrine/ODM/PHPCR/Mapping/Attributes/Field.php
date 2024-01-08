<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Field implements MappingAttribute
{
    public function __construct(
        public null|string $property = null,
        public string $type = 'undefined',
        public bool $multivalue = false,
        public null|string $assoc = null,
        public bool $nullable = false,
        public bool $translated = false,
    ) {
    }
}
