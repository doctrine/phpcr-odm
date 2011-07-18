<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="cms_group")
 */
class CmsGroup
{
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\String */
    public $name;

    /** @PHPCRODM\ReferenceMany(targetDocument="CmsUser", mappedBy="groups") */
    public $users;

    /** @PHPCRODM\String(multivalue=true) */
    public $values;

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function addUser(CmsUser $user) {
        $this->users[] = $user;
    }

    public function getUsers() {
        return $this->users;
    }

    public function addValues($value) {
        $this->values[] = $value;
    }

    public function getValues() {
        return $this->values;
    }
}

