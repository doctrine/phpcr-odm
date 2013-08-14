<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document()
 */
class RefTestPrivateObj
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\ReferenceOne(cascade="persist") */
    private $reference;

    public function getReference()
    {
        return $this->reference;
    }

    public function setReference($ref)
    {
        $this->reference = $ref;
    }
}
