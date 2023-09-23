<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\PostUpdate as BasePostUpdate;

#[Attribute(Attribute::TARGET_METHOD)]
final class PostUpdate extends BasePostUpdate
{
}
