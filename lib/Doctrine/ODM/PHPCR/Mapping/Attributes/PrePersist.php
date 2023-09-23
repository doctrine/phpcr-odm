<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PrePersist as BasePrePersist;

#[Attribute(Attribute::TARGET_METHOD)]
final class PrePersist extends BasePrePersist
{
}
