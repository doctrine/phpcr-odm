<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 *
 * @Target("PROPERTY")
 */
final class Id
{
    /**
     * @var bool
     */
    public $id = true;

    /**
     * @var string
     */
    public $type = 'string';

    /**
     * @var string
     */
    public $strategy;
}
