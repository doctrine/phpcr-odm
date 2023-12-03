<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

/**
 * base class for the reference types.
 */
class Reference
{
    /**
     * The PHPCR property name to use.
     */
    public string $property;

    public string $targetDocument;
    public string $strategy = 'weak';

    /**
     * @var array or string, but we can't annotate that here, it confuses the annotation parser
     */
    public $cascade = [];
}
