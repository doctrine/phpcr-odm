<?php

namespace Doctrine\ODM\PHPCR\Id;

/**
 * Interface to be implemented by repositories that should act as id generator.
 */
interface RepositoryIdInterface
{
    public function generateId(object $document, ?object $parent = null): string;
}
