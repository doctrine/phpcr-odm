<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PostPersist as BasePostPersist;

#[Attribute(Attribute::TARGET_METHOD)]
final class PostPersist extends BasePostPersist
{
}
