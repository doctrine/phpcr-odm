<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Referrers as BaseReferrers;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Referrers extends BaseReferrers
{
}
