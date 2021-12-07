<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

/**
 * base class for all property types.
 */
class Property
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
    public $type = 'undefined';

    /**
     * @var bool
     */
    public $multivalue = false;

    /**
     * @var string
     */
    public $assoc;

    /**
     * @var bool
     */
    public $nullable = false;
}
