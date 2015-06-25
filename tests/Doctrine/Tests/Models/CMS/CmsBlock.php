<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document
 */
class CmsBlock
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\PhpArray */
    public $config;
}

