<?php

namespace Doctrine\Tests\ODM\PHPCR\Mapping\Model;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

/**
 * A class that uses referrers.
 */
#[PHPCR\Document(referenceable: true)]
class ReferrersMappingObject
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\MixedReferrers]
    public $allReferrers;

    #[PHPCR\Referrers(referencedBy: 'referenceManyWeak', referringDocument: ReferenceManyMappingObject::class)]
    public $filteredReferrers;

    #[PHPCR\MixedReferrers(referenceType: 'hard')]
    public $hardReferrers;

    #[PHPCR\MixedReferrers(referenceType: 'weak')]
    public $weakReferrers;
}
