<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class MixedReferrers implements MappingAttribute
{
    public function __construct(
        public ?string $referenceType = null
    ) {
    }
}
