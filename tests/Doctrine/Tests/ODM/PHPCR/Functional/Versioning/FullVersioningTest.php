<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional\Versioning;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(versionable: 'full')]
class FullVersionTestObj
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

class FullVersioningTest extends VersioningTestAbstract
{
    public function setUp(): void
    {
        $this->typeVersion = FullVersionTestObj::class;
        parent::setUp();
    }
}
