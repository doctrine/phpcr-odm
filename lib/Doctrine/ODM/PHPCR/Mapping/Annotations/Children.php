<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Children
{
    /**
     * @var array or string or null, but we can't annotate that here, it confuses the annotation parser
     */
    public $filter;
    public int $fetchDepth = -1;
    public bool $ignoreUntranslated = true;

    /**
     * @var array or string, but we can't annotate that here, it confuses the annotation parser
     */
    public $cascade = [];
}
