<?php

/**
 * !!! WARNING !!!
 *
 * This class is invalid as it uses an invalid key for the @PHPCRODM\Document(translator) annotation.
 * This class is supposed to throw an exception when it is read by the ODM !!!
 */

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(translator: 'some_unexisting_strategy')]
class InvalidMapping
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Locale]
    public $locale = 'en';

    #[PHPCR\Field(type: 'string', translated: true)]
    public $topic;
}
