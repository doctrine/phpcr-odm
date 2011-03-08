<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class RepositoryPathGenerator extends IdGenerator
{
    /**
     * @param object $document
     * @param ClassMetadata $cm
     * @param DocumentManager $dm
     * @return string
     */
    public function generate($document, ClassMetadata $cm, DocumentManager $dm)
    {
        $repository = $dm->getRepository($cm->class);
        if (!($repository instanceof RepositoryPathInterface)) {
            throw new \Exception("no id");
        }

        // TODO: should we have some default implementation (parent path + some md5/object id)?
        $id = $repository->generatePath($document);
        if (!$id) {
            throw new \Exception("no id");
        }
        return $id;
    }
}
