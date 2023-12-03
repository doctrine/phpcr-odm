<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\Document as BaseDocument;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Document extends BaseDocument implements MappingAttribute
{
    public function __construct(
        string $nodeType = null,
        string $repositoryClass = null,
        string $translator = null,
        array $mixins = null,
        bool $inheritMixins = true,
        string $versionable = null,
        bool $referenceable = false,
        bool $uniqueNodeType = false,
        array $childClasses = [],
        bool $isLeaf = false,
    ) {
        $this->nodeType = $nodeType;
        $this->repositoryClass = $repositoryClass;
        $this->translator = $translator;
        $this->mixins = $mixins;
        $this->inheritMixins = $inheritMixins;
        $this->versionable = $versionable;
        $this->referenceable = $referenceable;
        $this->uniqueNodeType = $uniqueNodeType;
        $this->childClasses = $childClasses;
        $this->isLeaf = $isLeaf;
    }
}
