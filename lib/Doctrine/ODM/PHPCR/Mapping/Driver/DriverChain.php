<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Mapping\MappingException;

/**
 * The DriverChain allows you to add multiple other mapping drivers for
 * certain namespaces
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class DriverChain implements Driver
{
    /**
     * @var array
     */
    private $drivers = array();

    /**
     * Add a nested driver.
     *
     * @param Driver $nestedDriver
     * @param string $namespace
     */
    public function addDriver(Driver $nestedDriver, $namespace)
    {
        $this->drivers[$namespace] = $nestedDriver;
    }

    /**
     * Get the array of nested drivers.
     *
     * @return array $drivers
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        foreach ($this->drivers as $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                $driver->loadMetadataForClass($className, $class);
                return;
            }
        }

        throw MappingException::classIsNotAValidDocument($className);
    }


    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames()
    {
        $classNames = array();
        foreach ($this->drivers as $driver) {
            $classNames = array_merge($classNames, $driver->getAllClassNames());
        }
        return $classNames;
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
        foreach ($this->drivers as $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                return $driver->isTransient($className);
            }
        }

        // class isTransient, i.e. not an document or mapped superclass
        return true;
    }
}
