<?php

namespace Doctrine\Tests\Models\References;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document]
class RefTestPrivateObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\ReferenceOne(cascade: 'persist')]
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
