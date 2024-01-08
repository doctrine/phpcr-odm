<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

abstract class Reference implements MappingAttribute
{
    public array|null $cascade;

    /**
     * @param string[]|string $cascade
     */
    public function __construct(
        /**
         * The PHPCR property name to use.
         */
        public null|string $property = null,
        public null|string $targetDocument = null,
        public string $strategy = 'weak',
        array|string $cascade = null
    ) {
        $this->cascade = null === $cascade ? null : (array) $cascade;
    }
}
