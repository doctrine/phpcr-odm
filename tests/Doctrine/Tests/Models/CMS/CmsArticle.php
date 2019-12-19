<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document
 */
class CmsArticle
{
    /** @PHPCRODM\Id */
    public $id;

    /** @PHPCRODM\Field(type="string") */
    public $topic;

    /** @PHPCRODM\Field(type="string") */
    public $text;

    /** @PHPCRODM\ReferenceOne(targetDocument="CmsUser") */
    public $user;

    public $comments;

    /** @PHPCRODM\ReferenceMany(targetDocument="CmsArticlePerson") */
    public $persons;

    /** @PHPCRODM\Field(type="binary", nullable=true) */
    public $attachments;

    public function __construct()
    {
        $this->persons = new ArrayCollection();
    }

    public function setAuthor(CmsUser $author)
    {
        $this->user = $author;
    }

    /**
     * @param CmsArticlePerson $person
     */
    public function addPerson(CmsArticlePerson $person)
    {
        $this->persons->add($person);
    }

    /**
     * @param mixed $persons
     */
    public function setPersons($persons)
    {
        $this->persons = $persons;
    }

    /**
     * @return mixed
     */
    public function getPersons()
    {
        return $this->persons;
    }
}
