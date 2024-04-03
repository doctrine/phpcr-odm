<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Child implements MappingAttribute
{
    public ?array $cascade;

    /**
     * @param string[]|string $cascade
     */
    public function __construct(
        public ?string $nodeName = null,
        array|string|null $cascade = null,
    ) {
        $this->cascade = null === $cascade ? null : (array) $cascade;
    }
}
