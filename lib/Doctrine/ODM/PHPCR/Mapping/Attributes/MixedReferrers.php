<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\MixedReferrers as BaseMixedReferrers;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MixedReferrers extends BaseMixedReferrers
{
}
