<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class AssignedPathGenerator extends IdGenerator
{
    /**
     * @param object $document
     * @param ClassMetadata $cm
     * @param DocumentManager $dm
     * @return array
     */
    public function generate($document, ClassMetadata $cm, DocumentManager $dm)
    {
        $id = $cm->getFieldValue($document, $cm->path);
        if (!$id) {
            throw new \Exception("no id");
        }
        return $id;
    }
}
