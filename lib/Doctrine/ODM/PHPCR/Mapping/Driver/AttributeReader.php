<?php

declare(strict_types=1);

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function assert;
use function is_string;
use function is_subclass_of;

/** @internal */
final class AttributeReader
{
    /**
     * @psalm-return class-string-map<T, T>
     *
     * @template T of MappingAttribute
     */
    public function getClassAttributes(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * @return class-string-map<T, T>
     *
     * @template T of MappingAttribute
     */
    public function getMethodAttributes(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /**
     * @return class-string-map<T, T>
     *
     * @template T of MappingAttribute
     */
    public function getPropertyAttributes(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @param array<ReflectionAttribute> $attributes
     *
     * @return class-string-map<T, T>
     *
     * @template T of MappingAttribute
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            assert(is_string($attributeName));
            // Make sure we only get Doctrine Attributes
            if (! is_subclass_of($attributeName, MappingAttribute::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof MappingAttribute);

            $instances[$attributeName] = $instance;
        }

        return $instances;
    }
}
