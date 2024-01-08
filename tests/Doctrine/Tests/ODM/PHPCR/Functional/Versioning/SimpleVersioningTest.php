<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(versionable: 'simple')]
class SimpleVersionTestObj
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Node]
    public $node;

    #[PHPCR\VersionName]
    public $versionName;

    #[PHPCR\VersionCreated]
    public $versionCreated;

    #[PHPCR\Field(type: 'string')]
    public $username;

    #[PHPCR\Field(type: 'long', multivalue: true)]
    public $numbers;

    #[PHPCR\ReferenceOne(strategy: 'weak')]
    public $reference;
}

class SimpleVersioningTest extends VersioningTestAbstract
{
    public function setUp(): void
    {
        $this->typeVersion = SimpleVersionTestObj::class;
        parent::setUp();
    }
}
