<?php

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Id\RepositoryIdInterface;
use Doctrine\ODM\PHPCR\Mapping\Attributes as PHPCR;

#[PHPCR\Document(repositoryClass: CmsArticlePersonRepository::class, referenceable: true)]
class CmsArticlePerson
{
    #[PHPCR\Id(strategy: 'repository')]
    public $id;

    #[PHPCR\Field(type: 'string', nullable: true)]
    public $name;

    #[PHPCR\Referrers(referencedBy: 'persons', referringDocument: CmsArticle::class, cascade: 'persist')]
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
     *
     * @return string
     */
    public function generateId($document, $parent = null)
    {
        return '/functional/'.$document->name;
    }
}
