<?php

namespace Documents;

namespace Doctrine\Tests\Models\Blog;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Comment will be a child of the Post in the test scenario
 *
 * Used for Join tests
 *
 * @PHPCRODM\Document()
 */
class NewsItem
{
    /** @PHPCRODM\NodeName() */
    public $name;

    /** @PHPCRODM\ParentDocument() */
    public $parent;

    /** @PHPCRODM\String */
    public $title;
}


