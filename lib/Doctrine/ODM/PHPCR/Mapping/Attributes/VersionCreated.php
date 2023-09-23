<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\VersionCreated as BaseVersionCreated;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class VersionCreated extends BaseVersionCreated
{
}
