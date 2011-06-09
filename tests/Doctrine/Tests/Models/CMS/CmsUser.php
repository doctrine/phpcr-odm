<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(alias="cms_user")
 */
class CmsUser
{
// /** @Id */
// public $id;
    /** @PHPCRODM\Id */
    public $id;
    /** @PHPCRODM\Node */
    public $node;
    /** @PHPCRODM\String(name="status") */
    public $status;
    /** @PHPCRODM\String(name="username") */
    public $username;
    /** @PHPCRODM\String(name="name") */
    public $name;

    /** @PHPCRODM\EmbedOne(name="address") */
    public $address;

    // * @ReferenceOne(targetDocument="CmsUserRights") */
    // public $rights;
    //
    // /**
    //  * @ReferenceMany(targetDocument="CmsArticle", mappedBy="user")
    //
    // public $articles;
    //
    // /** @ReferenceMany(targetDocument="CmsGroup") */
    // public $groups;

    public function __construct() {
        $this->articles = new ArrayCollection;
        $this->groups = new ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getName() {
        return $this->name;
    }

    public function addArticle(CmsArticle $article) {
        $this->articles[] = $article;
        $article->setAuthor($this);
    }

    public function addGroup(CmsGroup $group) {
        $this->groups[] = $group;
        $group->addUser($this);
    }

    public function getGroups() {
        return $this->groups;
    }

    public function getAddress() { return $this->address; }

    public function setAddress(CmsAddress $address) {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}
