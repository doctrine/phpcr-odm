<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Exception\InvalidArgumentException;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

/**
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
abstract class IdGenerator
{
    /**
     * Factory method for the predefined strategies.
     */
    public static function create(int $generatorType): IdGenerator
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
     */
    abstract public function generate(object $document, ClassMetadata $class, DocumentManagerInterface $dm, object $parent = null): string;
}
