<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PostLoad as BasePostLoad;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class PostLoad extends BasePostLoad implements MappingAttribute
{
}
