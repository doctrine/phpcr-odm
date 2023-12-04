<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;
use mysql_xdevapi\CrudOperationBindable;

#[PHPCR\Document]
class CmsArticle
{
    #[PHPCR\Id]
    public $id;

    #[PHPCR\Field(type: 'string')]
    public $topic;

    #[PHPCR\Field(type: 'string')]
    public $text;

    #[PHPCR\ReferenceOne(targetDocument: CmsUser::class)]
    public $user;

    public $comments;

    #[PHPCR\ReferenceMany(targetDocument: CmsArticlePerson::class)]
    public $persons;

    #[PHPCR\Field(type: 'binary', nullable: true)]
    public $attachments;

    public function __construct()
    {
        $this->persons = new ArrayCollection();
    }

    public function setAuthor(CmsUser $author)
    {
        $this->user = $author;
    }

    public function addPerson(CmsArticlePerson $person)
    {
        $this->persons->add($person);
    }

    public function setPersons($persons)
    {
        $this->persons = $persons;
    }

    public function getPersons()
    {
        return $this->persons;
    }
}
