<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 *
 * @Target("PROPERTY")
 */
final class Referrers
{
    /**
     * Name of the field in the other document referencing this document.
     *
     * @var string
     */
    public $referencedBy;

    /**
     * @var string
     */
    public $referringDocument;

    /**
     * @var array
     */
    public $cascade = [];
}
