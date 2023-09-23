<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PreUpdate as BasePreUpdate;

#[Attribute(Attribute::TARGET_METHOD)]
final class PreUpdate extends BasePreUpdate
{
}
