<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class CmsGroup
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String() */
    public $name;

    /** @PHPCRODM\ReferenceMany(targetDocument="CmsUser", cascade="persist") */
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

