<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\Children as BaseChildren;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Children extends BaseChildren implements MappingAttribute
{
    public function __construct(
        array|string $filter = null,
        int $fetchDepth = -1,
        bool $ignoreUntranslated = true,
        array|string $cascade = [],
    ) {
        $this->filter = $filter ? (array) $filter : null;
        $this->fetchDepth = $fetchDepth;
        $this->ignoreUntranslated = $ignoreUntranslated;
        $this->cascade = (array) $cascade;
    }
}
