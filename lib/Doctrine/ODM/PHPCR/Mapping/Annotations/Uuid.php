<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 *
 * @Target("PROPERTY")
 */
final class Uuid extends Property
{
    /**
     * @var string
     */
    public $property = 'jcr:uuid';

    /**
     * @var string
     */
    public $type = 'string';
}
