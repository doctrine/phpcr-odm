<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Child
{
    /**
     * PHPCR node name of the child to map.
     *
     * @var string
     */
    public $nodeName;

    /**
     * @var array
     */
    public $cascade = [];
}
