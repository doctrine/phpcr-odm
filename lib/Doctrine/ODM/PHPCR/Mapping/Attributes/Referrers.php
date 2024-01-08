<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Referrers implements MappingAttribute
{
    public array|null $cascade;

    /**
     * @param string[]|string $cascade
     */
    public function __construct(
        public string $referencedBy,
        public string $referringDocument,
        array|string $cascade = null
    ) {
        $this->cascade = null === $cascade ? null : (array) $cascade;
    }
}
