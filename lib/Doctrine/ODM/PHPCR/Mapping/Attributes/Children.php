<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Children implements MappingAttribute
{
    public ?array $filter;
    public ?array $cascade;

    /**
     * @param string[]|string $filter
     * @param string[]|string $cascade
     */
    public function __construct(
        array|string|null $filter = null,
        public int $fetchDepth = -1,
        public bool $ignoreUntranslated = true,
        array|string|null $cascade = null,
    ) {
        $this->filter = null === $filter ? null : (array) $filter;
        $this->cascade = null === $cascade ? null : (array) $cascade;
    }
}
