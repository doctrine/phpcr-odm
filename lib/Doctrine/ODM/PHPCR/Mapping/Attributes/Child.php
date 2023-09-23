<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Child as BaseChild;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Child extends BaseChild
{
}
