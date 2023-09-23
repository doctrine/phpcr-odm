<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Uuid as BaseUuid;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Uuid extends BaseUuid
{
}
