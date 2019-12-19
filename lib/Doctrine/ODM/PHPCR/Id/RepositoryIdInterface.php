<?php

namespace Doctrine\ODM\PHPCR\Id;

/**
 * Interface to be implemented by repositories that should act as id generator.
 */
interface RepositoryIdInterface
{
    /**
     * Generate a document id.
     *
     * @param object $document
     * @param object $parent
     *
     * @return string the id for this document
     */
    public function generateId($document, $parent = null);
}
