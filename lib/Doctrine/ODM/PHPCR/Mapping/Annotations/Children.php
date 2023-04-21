<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 *
 * @Target("PROPERTY")
 */
final class Children
{
    /**
     * @var array
     */
    public $filter;

    /**
     * @var int
     */
    public $fetchDepth = -1;

    /**
     * @var bool
     */
    public $ignoreUntranslated = true;

    /**
     * @var array
     */
    public $cascade = [];
}
