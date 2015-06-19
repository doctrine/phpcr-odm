<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that extends a class which contains string properties
 *
 * @PHPCRODM\Document(translator="attribute")
 */
class StringExtendedMappingObject extends StringMappingObject
{
    /**
     * The language this document currently is in
     * @PHPCRODM\Locale
     */
    private $doclocale;

    /** @PHPCRODM\Field(type="string", translated=true) */
    public $stringAssoc;
}
