<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;

/**
 * The BuiltinDocumentsDriver is used internally to make sure
 * that the mapping for the built-in documents can be loaded
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Uwe Jäger <uwej711e@googlemail.com>
 */
class BuiltinDocumentsDriver implements Driver
{
    /**
     * namespace of built-in documents
     */
    const NAME_SPACE = 'Doctrine\ODM\PHPCR\Document';

    /**
     * @var Driver
     */
    private $wrappedDriver;

    /**
     * @var Driver
     */
    private $builtinDriver;

    /**
     * Create with a driver to wrap
     *
     * @param Driver $nestedDriver
     * @param string $namespace
     */
    public function __construct(Driver $wrappedDriver)
    {
        $this->wrappedDriver = $wrappedDriver;

        $reader = new AnnotationReader();
        $this->builtinDriver = new AnnotationDriver($reader, array(realpath(__DIR__.'/../../Document')));
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        if (strpos($className, self::NAME_SPACE) === 0) {
            $this->builtinDriver->loadMetadataForClass($className, $class);
            return;
        }

        $this->wrappedDriver->loadMetadataForClass($className, $class);
    }


    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames()
    {
        return array_merge(
            $this->builtinDriver->getAllClassNames(),
            $this->wrappedDriver->getAllClassNames()
        );
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     *
     * This is only the case for non-transient classes either mapped as an Document or MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        if (strpos($className, self::NAME_SPACE) === 0) {
            return $this->builtinDriver->isTransient($className);
        }

        return $this->wrappedDriver->isTransient($className);
    }
}