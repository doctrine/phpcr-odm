<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class that uses referrers
 * 
 * @PHPCRODM\Document(referenceable=true)
 */
class ReferrersMappingObject
{
    /** @PHPCRODM\Id */
    public $id;

    /**
     * @PHPCRODM\Referrers
     */
    public $allReferrers;

    /**
     * @PHPCRODM\Referrers(filter="test_filter")
     */
    public $filteredReferrers;

    /**
     * @PHPCRODM\Referrers(referenceType="hard")
     */
    public $hardReferrers;

    /**
     * @PHPCRODM\Referrers(referenceType="weak")
     */
    public $weakReferrers;
}
