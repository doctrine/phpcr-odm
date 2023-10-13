<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\MixedReferrers as BaseMixedReferrers;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class MixedReferrers extends BaseMixedReferrers implements MappingAttribute
{
    public function __construct(string $referenceType = null)
    {
        $this->referenceType = $referenceType;
    }
}
