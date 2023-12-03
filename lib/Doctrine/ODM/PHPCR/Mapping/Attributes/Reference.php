<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\Reference as BaseReference;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

abstract class Reference extends BaseReference implements MappingAttribute
{
    /**
     * @param string[] $cascade
     */
    public function __construct(
        string $property = null,
        string $targetDocument = null,
        string $strategy = 'weak',
        array $cascade = []
    ) {
        $this->property = $property;
        $this->targetDocument = $targetDocument;
        $this->strategy = $strategy;
        $this->cascade = $cascade;
    }
}
