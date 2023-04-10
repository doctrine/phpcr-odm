<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * The parent of this node as in PHPCR\NodeInterface::getParent
 * Parent is a reserved keyword in php, thus we use ParentDocument as name.
 *
 * @Annotation
 *
 * @Target("PROPERTY")
 */
final class ParentDocument
{
    /**
     * @var array or string, but we can't annotate that here, it confuses the annotation parser
     */
    public $cascade = [];
}
