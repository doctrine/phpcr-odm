<?php

namespace Documents;

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(isLeaf: true)]
class CmsBlogPost
{
    #[PHPCR\Id(strategy: 'parent')]
    public $id;

    #[PHPCR\Nodename]
    public $name;

    #[PHPCR\ParentDocument]
    public $parent;
}
