<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\ReferenceMany as BaseReferenceMany;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceMany extends BaseReferenceMany
{
}
