<?php

namespace Doctrine\Tests\Models\Translation;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(translator="child", referenceable=true)
 */
class ChildTranslationArticle extends Article
{
}
