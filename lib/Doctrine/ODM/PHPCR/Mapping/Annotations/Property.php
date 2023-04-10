<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

/**
 * base class for all property types.
 */
class Property
{
    /**
     * The PHPCR property name to use.
     */
    public string $property;

    public string $type = 'undefined';
    public bool $multivalue = false;
    public ?string $assoc = null;
    public bool $nullable = false;
}
