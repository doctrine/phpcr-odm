<?php

declare(strict_types=1);

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

/** @internal */
final class AttributeReader
{
    /**
     * @return MappingAttribute[]
     */
    public function getClassAttributes(\ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * @return MappingAttribute[]
     */
    public function getMethodAttributes(\ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /**
     * @return MappingAttribute[]
     */
    public function getPropertyAttributes(\ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @return MappingAttribute[]
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            \assert(\is_string($attributeName));
            // Make sure we only get Doctrine Attributes
            if (!\is_subclass_of($attributeName, MappingAttribute::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            \assert($instance instanceof MappingAttribute);

            $instances[$attributeName] = $instance;
        }

        return $instances;
    }
}
