<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Id as BaseId;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id extends BaseId implements MappingAttribute
{
    public function __construct(
        bool $id = true,
        string $type = 'string',
        string $strategy = null,
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->strategy = $strategy;
    }
}
