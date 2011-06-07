<?php

namespace Doctrine\ODM\PHPCR\Id;

interface RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    function generateId($document);
}
