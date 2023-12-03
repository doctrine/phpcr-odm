<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Referrers
{
    /**
     * Name of the field in the other document referencing this document.
     */
    public string $referencedBy;

    public string $referringDocument;

    /**
     * @var array or string, but we can't annotate that here, it confuses the annotation parser
     */
    public $cascade = [];
}
