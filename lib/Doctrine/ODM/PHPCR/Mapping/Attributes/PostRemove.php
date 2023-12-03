<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\PostRemove as BasePostRemove;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class PostRemove extends BasePostRemove implements MappingAttribute
{
}
