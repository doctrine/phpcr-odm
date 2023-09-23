<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\ReferenceOne as BaseReferenceOne;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ReferenceOne extends BaseReferenceOne
{
}
