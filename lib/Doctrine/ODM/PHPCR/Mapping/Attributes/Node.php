<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Attribute;
use Doctrine\ODM\PHPCR\Mapping\Annotations\Node as BaseNode;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Node extends BaseNode
{
}
