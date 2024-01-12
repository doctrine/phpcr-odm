<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Exception\BadMethodCallException;

/**
 * Immutable referrer collection class.
 *
 * This class represents a collection of referrers of a document that can be
 * mixed and thus never can be persisted.
 */
class ImmutableReferrersCollection extends ReferrersCollection
{
    public function __construct(DocumentManagerInterface $dm, $document, $type = null, $locale = null)
    {
        parent::__construct($dm, $document, $type, null, $locale);
    }

    public function add($element): never
    {
        throw new BadMethodCallException('Can not call '.__METHOD__.' on immutable collection');
    }

    public function clear(): never
    {
        throw new BadMethodCallException('Can not call '.__METHOD__.' on immutable collection');
    }

    public function offsetSet($offset, $value): never
    {
        throw new BadMethodCallException('Can not call '.__METHOD__.' on immutable collection');
    }

    public function offsetUnset($offset): never
    {
        throw new BadMethodCallException('Can not call '.__METHOD__.' on immutable collection');
    }

    public function remove($key): never
    {
        throw new BadMethodCallException('Can not call '.__METHOD__.' on immutable collection');
    }

    public function removeElement($element): never
    {
        throw new BadMethodCallException('Can not call '.__METHOD__.' on immutable collection');
    }

    public function set($key, $value): never
    {
        throw new BadMethodCallException('Can not call '.__METHOD__.' on immutable collection');
    }
}
