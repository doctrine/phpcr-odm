<?php

namespace Doctrine\ODM\PHPCR;

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

    // TODO: overwrite all methods that would modify this collection and throw exceptions
}
