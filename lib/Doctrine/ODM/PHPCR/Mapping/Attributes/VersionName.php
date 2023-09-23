<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\VersionName as BaseVersionName;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class VersionName extends BaseVersionName
{
}
