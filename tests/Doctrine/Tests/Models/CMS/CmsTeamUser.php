<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

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
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return $document->parent->id.'/'.$document->username;
    }
}
