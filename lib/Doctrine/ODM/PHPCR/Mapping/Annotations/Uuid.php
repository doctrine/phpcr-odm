<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Uuid extends Property
{
    public string $property = 'jcr:uuid';
    public string $type = 'string';
}
