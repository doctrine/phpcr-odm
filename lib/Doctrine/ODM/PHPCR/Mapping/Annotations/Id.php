<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Id
{
    public bool $id = true;
    public string $type = 'string';
    public ?string $strategy = null;
}
