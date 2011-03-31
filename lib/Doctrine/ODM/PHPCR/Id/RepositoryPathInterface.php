<?php

namespace Doctrine\ODM\PHPCR\Id;

interface RepositoryPathInterface
{
    /**
     * @param object $document
     * @return string
     */
    function generatePath($document);
}
