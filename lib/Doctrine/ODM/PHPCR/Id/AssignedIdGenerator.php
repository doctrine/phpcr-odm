<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class AssignedIdGenerator extends IdGenerator
{
    /**
     * @param object $document
     * @param ClassMetadata $cm
     * @param DocumentManager $dm
     * @return string
     */
    public function generate($document, ClassMetadata $cm, DocumentManager $dm)
    {
        $id = $cm->getFieldValue($document, $cm->identifier);
        if (!$id) {
            throw new \Exception("No Id found. Make sure your document has a field with @phpcr:Id annotation and that you set that field to the path where you want to store the document.");
        }
        return $id;
    }
}
