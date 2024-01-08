<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses the repository strategy to generate IDs.
 */
#[PHPCR\Document(versionable: 'simple')]
class VersionableMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\VersionName]
    private $versionName;

    #[PHPCR\VersionCreated]
    private $versionCreated;
}
