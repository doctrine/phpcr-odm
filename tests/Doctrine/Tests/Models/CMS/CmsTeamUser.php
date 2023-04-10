<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsTeamUserRepository")
 */
class CmsTeamUser extends CmsUser
{
    /**
     * @PHPCRODM\ParentDocument
     */
    public $parent;

    /**
     * @PHPCRODM\Nodename
     */
    public $nodename;

    public function setParentDocument($parent)
    {
        $this->parent = $parent;
    }
}

class CmsTeamUserRepository extends DocumentRepository implements RepositoryIdInterface
{
    public function generateId(object $document, ?object $parent = null): string
    {
        return $document->parent->id.'/'.$document->username;
    }
}
