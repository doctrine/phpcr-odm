<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that uses the repository strategy to generate IDs
 * 
 * @PHPCRODM\Document(versionable="simple")
 */
class VersionableMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\VersionName */
    private $versionName;

    /** @PHPCRODM\VersionCreated */
    private $versionCreated;
}
