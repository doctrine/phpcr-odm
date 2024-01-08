<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Uuid extends Field
{
    public function __construct(
        string $property = 'jcr:uuid',
        string $type = 'string',
        bool $multivalue = false,
        string $assoc = null,
        bool $nullable = false,
    ) {
        parent::__construct($property, $type, $multivalue, $assoc, $nullable, false);
    }
}
