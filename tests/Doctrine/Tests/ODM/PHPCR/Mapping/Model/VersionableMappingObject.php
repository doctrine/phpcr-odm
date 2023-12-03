<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses the repository strategy to generate IDs.
 *
 * @PHPCRODM\Document(versionable="simple")
 */
#[PHPCR\Document(versionable: 'simple')]
class VersionableMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /** @PHPCRODM\VersionName */
    #[PHPCR\VersionName]
    private $versionName;

    /** @PHPCRODM\VersionCreated */
    #[PHPCR\VersionCreated]
    private $versionCreated;
}
