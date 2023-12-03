<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses referrers
 *
 * @PHPCRODM\Document(referenceable=true)
 */
#[PHPCR\Document(referenceable: true)]
class ReferrersMappingObject
{
    /** @PHPCRODM\Id */
    #[PHPCR\Id]
    public $id;

    /**
     * @PHPCRODM\MixedReferrers
     */
    #[PHPCR\MixedReferrers]
    public $allReferrers;

    /**
     * @PHPCRODM\Referrers(referencedBy="referenceManyWeak", referringDocument="ReferenceManyMappingObject")
     */
    #[PHPCR\Referrers(referencedBy: 'referenceManyWeak', referringDocument: ReferenceManyMappingObject::class)]
    public $filteredReferrers;

    /**
     * @PHPCRODM\MixedReferrers(referenceType="hard")
     */
    #[PHPCR\MixedReferrers(referenceType: 'hard')]
    public $hardReferrers;

    /**
     * @PHPCRODM\MixedReferrers(referenceType="weak")
     */
    #[PHPCR\MixedReferrers(referenceType: 'weak')]
    public $weakReferrers;
}
