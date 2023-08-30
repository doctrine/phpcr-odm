<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsArticlePersonRepository", referenceable=true)
 */
class CmsArticlePerson
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;

    /** @PHPCRODM\Field(type="string", nullable=true) */
    public $name;

    /** @PHPCRODM\Referrers(referencedBy="persons", referringDocument="Doctrine\Tests\Models\CMS\CmsArticle", cascade="persist") */
    public $articlesReferrers;

    public function __construct()
    {
        $this->articlesReferrers = new ArrayCollection();
    }

    public function setArticlesReferrers($articlesReferrers)
    {
        $this->articlesReferrers = $articlesReferrers;
    }

    public function getArticlesReferrers()
    {
        return $this->articlesReferrers;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

class CmsArticlePersonRepository extends DocumentRepository implements RepositoryIdInterface
{
    public function generateId(object $document, object $parent = null): string
    {
        return '/functional/'.$document->name;
    }
}
