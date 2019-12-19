<?php

namespace Doctrine\ODM\PHPCR\Mapping\Annotations;

/**
 * Base class for all the translatable properties (i.e. every property but Uuid and Version).
 */
class TranslatableProperty extends Property
{
    /**
     * @var bool
     */
    public $translated = false;
}
