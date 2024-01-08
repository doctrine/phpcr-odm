<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(referenceable: true)]
class CmsGroup
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $name;

    #[PHPCR\ReferenceMany(targetDocument: CmsUser::class, cascade: 'persist')]
    public $users;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addUser(CmsUser $user)
    {
        $this->users[] = $user;
    }

    public function getUsers()
    {
        return $this->users;
    }
}
