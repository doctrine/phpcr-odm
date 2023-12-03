<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * The BuiltinDocumentsDriver is used internally to make sure
 * that the mapping for the built-in documents can be loaded
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @see        www.doctrine-project.org
 * @since       1.0
 *
 * @author      Uwe Jäger <uwej711e@googlemail.com>
 */
class BuiltinDocumentsDriver implements MappingDriver
{
    /**
     * namespace of built-in documents
     */
    const NAME_SPACE = 'Doctrine\ODM\PHPCR\Document';

    private MappingDriver $wrappedDriver;
    private AttributeDriver $builtinDriver;

    /**
     * Create with a driver to wrap
     */
    public function __construct(MappingDriver $wrappedDriver)
    {
        $this->wrappedDriver = $wrappedDriver;

        $this->builtinDriver = new AttributeDriver([realpath(__DIR__.'/../../Document')]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        if (str_starts_with($className, self::NAME_SPACE)) {
            $this->builtinDriver->loadMetadataForClass($className, $class);

            return;
        }

        $this->wrappedDriver->loadMetadataForClass($className, $class);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames()
    {
        return array_merge(
            $this->builtinDriver->getAllClassNames(),
            $this->wrappedDriver->getAllClassNames()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        if (0 === strpos($className, self::NAME_SPACE)) {
            return $this->builtinDriver->isTransient($className);
        }

        return $this->wrappedDriver->isTransient($className);
    }
}

interface_exists(ClassMetadata::class);
