<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\DocumentRepository as BaseDocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

/**
 * A class that contains mapped children via properties
 */
class BarfooRepository extends BaseDocumentRepository
{
    public function generateId($document, $parent = null)
    {
        return '/functional/' . rand();
    }
}
