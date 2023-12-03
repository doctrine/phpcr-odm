<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\PostPersist as BasePostPersist;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class PostPersist extends BasePostPersist implements MappingAttribute
{
}
