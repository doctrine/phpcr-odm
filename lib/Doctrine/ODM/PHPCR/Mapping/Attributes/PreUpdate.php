<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PreUpdate as BasePreUpdate;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class PreUpdate extends BasePreUpdate implements MappingAttribute
{
}
