<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PostLoad as BasePostLoad;

#[Attribute(Attribute::TARGET_METHOD)]
final class PostLoad extends BasePostLoad
{
}
