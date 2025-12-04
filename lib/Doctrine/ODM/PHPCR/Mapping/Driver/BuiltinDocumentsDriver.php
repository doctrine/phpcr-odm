<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * The BuiltinDocumentsDriver is used internally to make sure
 * that the mapping for the built-in documents can be loaded.
 *
 * @author Uwe Jäger <uwej711e@googlemail.com>
 */
class BuiltinDocumentsDriver implements MappingDriver
{
    /**
     * namespace of built-in documents.
     */
    public const NAME_SPACE = 'Doctrine\ODM\PHPCR\Document';

    private MappingDriver $wrappedDriver;

    private AttributeDriver $builtinDriver;

    public function __construct(MappingDriver $wrappedDriver)
    {
        $this->wrappedDriver = $wrappedDriver;

        $this->builtinDriver = new AttributeDriver([realpath(__DIR__.'/../../Document')]);
    }

    public function loadMetadataForClass($className, ClassMetadata $metadata): void
    {
        if (str_starts_with($className, self::NAME_SPACE)) {
            $this->builtinDriver->loadMetadataForClass($className, $metadata);

            return;
        }

        $this->wrappedDriver->loadMetadataForClass($className, $metadata);
    }

    public function getAllClassNames(): array
    {
        return array_merge(
            $this->builtinDriver->getAllClassNames(),
            $this->wrappedDriver->getAllClassNames()
        );
    }

    public function isTransient($className): bool
    {
        if (str_starts_with($className, self::NAME_SPACE)) {
            return $this->builtinDriver->isTransient($className);
        }

        return $this->wrappedDriver->isTransient($className);
    }
}

interface_exists(ClassMetadata::class);
