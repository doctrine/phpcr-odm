<?php

namespace Doctrine\ODM\PHPCR\Id;

interface RepositoryIdInterface
{
    /**
     * @param object $document
     * @return string
     */
    function generateId($document);
}
