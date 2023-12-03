<?php

namespace Doctrine\ODM\PHPCR\Mapping\Attributes;

use Doctrine\ODM\PHPCR\Mapping\Annotations\PrePersist as BasePrePersist;
use Doctrine\ODM\PHPCR\Mapping\MappingAttribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class PrePersist extends BasePrePersist implements MappingAttribute
{
}
