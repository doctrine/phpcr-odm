<?php

namespace Doctrine\ODM\PHPCR\Id;

interface RepositoryPathGenerator
{
    /**
     * @param object $document
     * @return string
     */
    function generatePath($document);
}
