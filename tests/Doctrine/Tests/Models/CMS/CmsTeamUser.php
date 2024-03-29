<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(repositoryClass: CmsTeamUserRepository::class)]
class CmsTeamUser extends CmsUser
{
    #[PHPCR\ParentDocument]
    public $parent;

    #[PHPCR\Nodename]
    public $nodename;

    public function setParentDocument($parent)
    {
        $this->parent = $parent;
    }
}

class CmsTeamUserRepository extends DocumentRepository implements RepositoryIdInterface
{
    public function generateId(object $document, object $parent = null): string
    {
        return $document->parent->id.'/'.$document->username;
    }
}
