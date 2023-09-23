<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Locale as BaseLocale;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Locale extends BaseLocale
{
}
