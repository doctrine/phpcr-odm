<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(translator: 'attribute')]
class DatedComment extends Comment
{
    #[PHPCR\Field(type: 'date')]
    public $date;
}
