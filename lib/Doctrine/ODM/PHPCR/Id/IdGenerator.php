<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadataInfo;

/**
 * Used to abstract ID generation
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 */
abstract class IdGenerator
{
    /**
     * @param  int $generatorType
     * @return IdGenerator
     */
    static public function create($generatorType)
    {
        switch ($generatorType) {
            case ClassMetadataInfo::GENERATOR_TYPE_NONE:
                $instance = new AssignedPathGenerator();
                break;
            case ClassMetadataInfo::GENERATOR_TYPE_REPOSITORY:
                $instance = new RepositoryPathGenerator();
                break;
            default:
                throw \Exception("ID Generator does not exist!");
        }
        return $instance;
    }

    /**
     * @param object $document
     * @param ClassMetadata $cm
     * @param DocumentManager $dm
     */
    abstract public function generate($document, ClassMetadata $cm, DocumentManager $dm);
}
