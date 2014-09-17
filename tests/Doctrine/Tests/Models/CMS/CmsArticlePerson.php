<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;

/**
 * @PHPCRODM\Document(repositoryClass="Doctrine\Tests\Models\CMS\CmsArticlePersonRepository", referenceable=true)
 */
class CmsArticlePerson
{
    /** @PHPCRODM\Id(strategy="repository") */
    public $id;
    /** @PHPCRODM\String(nullable=true) */
    public $name;
    /** @PHPCRODM\Referrers(referencedBy="persons", referringDocument="Doctrine\Tests\Models\CMS\CmsArticle", cascade="persist") */
    public $articlesReferrers;

    public function __construct()
    {
        $this->articlesReferrers = new ArrayCollection();
    }

    /**
     * @param mixed $articlesReferrers
     */
    public function setArticlesReferrers($articlesReferrers)
    {
        $this->articlesReferrers = $articlesReferrers;
    }

    /**
     * @return mixed
     */
    public function getArticlesReferrers()
    {
        return $this->articlesReferrers;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

}

class CmsArticlePersonRepository extends DocumentRepository implements RepositoryIdInterface
{
    /**
     * Generate a document id
     *
     * @param object $document
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/'.$document->name;
    }
}
