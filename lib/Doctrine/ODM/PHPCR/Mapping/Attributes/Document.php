<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Document implements MappingAttribute
{
    public array|null $mixins;
    public array|null $childClasses;

    public function __construct(
        public null|string $nodeType = null,
        public null|string $repositoryClass = null,
        public null|string $translator = null,
        string|array $mixins = null,
        public bool|null $inheritMixins = null,
        public null|string $versionable = null,
        public null|bool $referenceable = null,
        public null|bool $uniqueNodeType = null,
        string|array $childClasses = null,
        public bool|null $isLeaf = null,
    ) {
        $this->mixins = null === $mixins ? null : (array) $mixins;
        $this->childClasses = null === $childClasses ? null : (array) $childClasses;
    }
}
