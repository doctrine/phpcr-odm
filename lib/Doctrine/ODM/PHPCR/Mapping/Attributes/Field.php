<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Field as BaseField;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Field extends BaseField implements MappingAttribute
{
    public function __construct(
        string $property = null,
        string $type = 'undefined',
        bool $multivalue = false,
        string $assoc = null,
        bool $nullable = false,
        bool $translated = false,
    )
    {
        $this->property = $property;
        $this->type = $type;
        $this->multivalue = $multivalue;
        $this->assoc = $assoc;
        $this->nullable = $nullable;
        $this->translated = $translated;
    }
}
