<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

/**
 *  The parent of this node as in PHPCR\NodeInterface::getParent.
 *
 *  Parent is a reserved keyword in php, thus we use ParentDocument as name.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ParentDocument implements MappingAttribute
{
    public array|null $cascade;

    public function __construct(
        string|array $cascade = null
    ) {
        $this->cascade = null === $cascade ? null : (array) $cascade;
    }
}
