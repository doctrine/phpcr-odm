<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PreRemove as BasePreRemove;

#[Attribute(Attribute::TARGET_METHOD)]
final class PreRemove extends BasePreRemove
{
}
