<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Depth as BaseDepth;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Depth extends BaseDepth implements MappingAttribute
{
}
