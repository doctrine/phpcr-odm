<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(
 *     childClasses={
 *         "Doctrine\Tests\Models\CMS\CmsBlogPost"
 *     }
 * )
 */
class CmsBlogFolder
{
    /**
     * @PHPCRODM\Id()
     */
    public $id;

    /**
     * @PHPCRODM\Children()
     */
    public $posts;
}
