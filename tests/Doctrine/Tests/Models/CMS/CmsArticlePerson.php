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
