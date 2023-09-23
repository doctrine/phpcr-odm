<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Field as BaseField;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field extends BaseField
{
}
