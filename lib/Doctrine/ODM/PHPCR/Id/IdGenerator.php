<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * Used to abstract ID generation.
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 *
 * @link        www.doctrine-project.com
 * @since       1.0
 *
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
abstract class IdGenerator
{
    /**
     * Factory method for the predefined strategies.
     *
     * @param int $generatorType
     *
     * @return IdGenerator
     */
    public static function create($generatorType)
    {
        switch ($generatorType) {
            case ClassMetadata::GENERATOR_TYPE_ASSIGNED:
                $instance = new AssignedIdGenerator();

                break;
            case ClassMetadata::GENERATOR_TYPE_REPOSITORY:
                $instance = new RepositoryIdGenerator();

                break;
            case ClassMetadata::GENERATOR_TYPE_PARENT:
                $instance = new ParentIdGenerator();

                break;
            case ClassMetadata::GENERATOR_TYPE_AUTO:
                $instance = new AutoIdGenerator();

                break;

            default:
                throw new InvalidArgumentException("ID Generator does not exist: $generatorType");
        }

        return $instance;
    }

    /**
     * Generate the actual id, to be overwritten by extending classes.
     *
     * @param object                   $document the object to create the id for
     * @param ClassMetadata            $class    class metadata of this object
     * @param DocumentManagerInterface $dm
     * @param object                   $parent
     *
     * @return string the id for this document
     */
    abstract public function generate($document, ClassMetadata $class, DocumentManagerInterface $dm, $parent = null);
}
