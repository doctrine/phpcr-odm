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
    public ?string $nodeType = null;
    public ?string $repositoryClass = null;
    public ?string $translator = null;

    /**
     * array|string.
     */
    public $mixins = [];

    public ?bool $inheritMixins = null;
    public ?string $versionable = null;
    public ?bool $referenceable = null;
    public ?bool $uniqueNodeType = null;
    public array $childClasses = [];
    public ?bool $isLeaf = null;
}
