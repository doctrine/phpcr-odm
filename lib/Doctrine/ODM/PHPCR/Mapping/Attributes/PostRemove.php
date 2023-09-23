<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PostRemove as BasePostRemove;

#[Attribute(Attribute::TARGET_METHOD)]
final class PostRemove extends BasePostRemove
{
}
