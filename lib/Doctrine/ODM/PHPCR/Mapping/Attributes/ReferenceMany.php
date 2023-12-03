<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceMany extends Reference
{
}
