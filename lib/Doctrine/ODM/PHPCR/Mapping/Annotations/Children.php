<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Children
{
    /**
     * This actually can be null too, but the legacy doctrine annotation driver gets confused if we declare a union type.
     *
     * @var array
     */
    public $filter;
    public int $fetchDepth = -1;
    public bool $ignoreUntranslated = true;

    /**
     * @var array or string, but we can't annotate that here, it confuses the annotation parser
     */
    public $cascade = [];
}
