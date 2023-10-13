<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Locale as BaseLocale;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Locale extends BaseLocale implements MappingAttribute
{
}
