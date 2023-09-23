<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\MappedSuperclass as BaseMappedSuperclass;

#[Attribute(Attribute::TARGET_CLASS)]
final class MappedSuperclass extends BaseMappedSuperclass
{
}
