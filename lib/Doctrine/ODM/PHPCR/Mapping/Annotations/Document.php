<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 *
 * @Target("CLASS")
 */
class Document
{
    /**
     * @var string
     */
    public $nodeType;

    /**
     * @var string
     */
    public $repositoryClass;

    /**
     * @var string
     */
    public $translator;

    /**
     * @var array
     */
    public $mixins;

    /**
     * @var bool
     */
    public $inheritMixins;

    /**
     * @var string
     */
    public $versionable;

    /**
     * @var bool
     */
    public $referenceable;

    /**
     * @var bool
     */
    public $uniqueNodeType;

    /**
     * @var array
     */
    public $childClasses = [];

    /**
     * @var bool
     */
    public $isLeaf;
}
