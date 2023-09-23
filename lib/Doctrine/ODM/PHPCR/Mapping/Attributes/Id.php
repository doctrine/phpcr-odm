<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Id as BaseId;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Id extends BaseId
{
}
