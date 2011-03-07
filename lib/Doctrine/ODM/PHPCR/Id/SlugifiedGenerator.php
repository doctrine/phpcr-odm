<?php

namespace Doctrine\ODM\PHPCR\Id;

use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

class SlugifiedGenerator extends IdGenerator
{
    /**
     * @param object $document
     * @param ClassMetadata $cm
     * @param DocumentManager $dm
     * @return array
     */
    public function generate($document, ClassMetadata $cm, DocumentManager $dm)
    {
        // TODO this needs to be cleaned up: "title" needs to defined differently etc.
        if (!$document->title) {
            throw new \Exception("no id");
        }

        $parent = $document->node->getParent();
        if (!$parent) {
            throw new \Exception("no id");
        }

        $id = $cm->getIdentifierValue($parent).'/'.$document->title;
        if (!$id) {
            throw new \Exception("no id");
        }
        return $id;
    }
}
