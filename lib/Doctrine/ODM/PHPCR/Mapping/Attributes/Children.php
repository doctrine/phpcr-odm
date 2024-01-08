<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Children implements MappingAttribute
{
    public array|null $filter;
    public array|null $cascade;

    /**
     * @param string[]|string $filter
     * @param string[]|string $cascade
     */
    public function __construct(
        array|string $filter = null,
        public int $fetchDepth = -1,
        public bool $ignoreUntranslated = true,
        array|string $cascade = null,
    ) {
        $this->filter = null === $filter ? null : (array) $filter;
        $this->cascade = null === $cascade ? null : (array) $cascade;
    }
}
