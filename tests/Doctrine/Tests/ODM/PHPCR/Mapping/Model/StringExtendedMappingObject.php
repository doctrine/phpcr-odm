<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that extends a class which contains string properties.
 */
#[PHPCR\Document(translator: 'attribute')]
class StringExtendedMappingObject extends StringMappingObject
{
    /**
     * The language this document currently is in.
     */
    #[PHPCR\Locale]
    private $doclocale;

    #[PHPCR\Field(type: 'string', translated: true)]
    public $stringAssoc;
}
