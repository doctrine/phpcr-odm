<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

/**
 * base class for the reference types.
 */
class Reference
{
    /**
     * The PHPCR property name to use.
     *
     * @var string
     */
    public $property;

    /**
     * @var string
     */
    public $targetDocument;

    /**
     * @var string
     */
    public $strategy = 'weak';

    /**
     * @var array
     */
    public $cascade = [];
}
