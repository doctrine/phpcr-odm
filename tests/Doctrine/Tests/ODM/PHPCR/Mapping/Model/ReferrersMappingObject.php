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
     * @PHPCRODM\MixedReferrers
     */
    public $allReferrers;

    /**
     * @PHPCRODM\Referrers(referencedBy="referenceManyWeak", referringDocument="ReferenceManyMappingObject")
     */
    public $filteredReferrers;

    /**
     * @PHPCRODM\MixedReferrers(referenceType="hard")
     */
    public $hardReferrers;

    /**
     * @PHPCRODM\MixedReferrers(referenceType="weak")
     */
    public $weakReferrers;
}
