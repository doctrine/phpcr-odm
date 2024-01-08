<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Id implements MappingAttribute
{
    public function __construct(
        public bool $id = true,
        public string $type = 'string',
        public null|string $strategy = null,
    ) {
    }
}
