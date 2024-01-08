<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Child implements MappingAttribute
{
    public array|null $cascade;

    /**
     * @param string[]|string $cascade
     */
    public function __construct(
        public null|string $nodeName = null,
        array|string $cascade = null,
    ) {
        $this->cascade = null === $cascade ? null : (array) $cascade;
    }
}
