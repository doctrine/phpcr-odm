<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(translator="attribute")
 */
class DatedComment extends Comment
{
    /**
     * @PHPCRODM\Date
     */
    public $date;
}
