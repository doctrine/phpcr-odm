<?php

namespace Documents;

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

/**
 * @PHPCRODM\Document(isLeaf=true)
 */
class CmsBlogPost
{
    /** @PHPCRODM\Id(strategy="parent") */
    public $id;

    /** @PHPCRODM\NodeName() */
    public $name;

    /** @PHPCRODM\ParentDocument() */
    public $parent;
}
