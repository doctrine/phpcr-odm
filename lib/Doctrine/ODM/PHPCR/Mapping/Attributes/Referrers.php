<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\Referrers as BaseReferrers;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Referrers extends BaseReferrers implements MappingAttribute
{
    /**
     * @param string[] $cascade
     */
    public function __construct(
        string $referencedBy,
        string $referringDocument,
        array|string $cascade = []
    ) {
        $this->referencedBy = $referencedBy;
        $this->referringDocument = $referringDocument;
        $this->cascade = (array) $cascade;
    }
}
