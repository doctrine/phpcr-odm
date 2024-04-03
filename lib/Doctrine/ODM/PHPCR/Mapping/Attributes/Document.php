<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Document implements MappingAttribute
{
    public ?array $mixins;
    public ?array $childClasses;

    public function __construct(
        public ?string $nodeType = null,
        public ?string $repositoryClass = null,
        public ?string $translator = null,
        string|array|null $mixins = null,
        public ?bool $inheritMixins = null,
        public ?string $versionable = null,
        public ?bool $referenceable = null,
        public ?bool $uniqueNodeType = null,
        string|array|null $childClasses = null,
        public ?bool $isLeaf = null,
    ) {
        $this->mixins = null === $mixins ? null : (array) $mixins;
        $this->childClasses = null === $childClasses ? null : (array) $childClasses;
    }
}
