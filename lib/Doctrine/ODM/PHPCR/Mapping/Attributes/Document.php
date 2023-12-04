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
        bool|null $inheritMixins = null,
        string $versionable = null,
        bool|null $referenceable = null,
        bool|null $uniqueNodeType = null,
        string|array|null $childClasses = null,
        bool|null $isLeaf = null,
    ) {
        $this->nodeType = $nodeType;
        $this->repositoryClass = $repositoryClass;
        $this->translator = $translator;
        $this->mixins = $mixins;
        $this->inheritMixins = $inheritMixins;
        $this->versionable = $versionable;
        $this->referenceable = $referenceable;
        $this->uniqueNodeType = $uniqueNodeType;
        $this->childClasses = $childClasses ? (array) $childClasses : null;
        $this->isLeaf = $isLeaf;
    }
}
