<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\DocumentRepository as BaseDocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

/**
 * A class that contains mapped children via properties
 */
class DocumentRepository extends BaseDocumentRepository implements RepositoryIdInterface
{
    public function generateId($document, $parent = null)
    {
        return '/functional/' . rand();
    }
}
