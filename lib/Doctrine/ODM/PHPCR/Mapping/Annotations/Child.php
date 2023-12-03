<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Child
{
    /**
     * PHPCR node name of the child to map.
     */
    public string $nodeName;

    /**
     * @var array or string, but we can't annotate that here, it confuses the annotation parser
     */
    public $cascade = [];
}
