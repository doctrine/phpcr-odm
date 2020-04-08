<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\DocumentRepository;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Tests\Models\Translation\Article;
use Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase;
use PHPCR\Util\PathHelper;
use PHPCR\Util\NodeHelper;

/**
 * @group functional
 */
class QueryBuilderTranslationTest extends PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    protected $dm;

    /**
     * @var \PHPCR\NodeInterface
     */
    protected $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(array('en' => array('de'), 'de' => array('en')), 'en'));

        $this->resetFunctionalNode($this->dm);


        $article = new Article();
        $article->id = '/functional/article';
        $article->author = 'John Doe';
        $article->topic = 'Some interesting subject';
        $article->text = 'Lorem ipsum...';

        $this->dm->persist($article);
        $this->dm->bindTranslation($article, 'en');

        $article->topic = 'Ein interessantes Thema';
        $article->text = 'Lorem ipsum...';

        $this->dm->bindTranslation($article, 'de');

        $this->dm->flush();
    }

    protected function createQb()
    {
        $qb = $this->dm->createQueryBuilder();
        return $qb;
    }

    public function testFindDe()
    {
        $de = $this->dm->findTranslation(null, '/functional/article', 'de');

        $this->assertEquals('Ein interessantes Thema', $de->topic);
    }

    public function testFindEn()
    {
        $en = $this->dm->findTranslation(null, '/functional/article', 'en');

        $this->assertEquals('Some interesting subject', $en->topic);
    }

    public function testDeQuery()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\Translation\Article', 'a');
        $qb->setLocale('de');
        $res = $qb->getQuery()->execute();

        $this->assertEquals('Ein interessantes Thema', $res->first()->topic);
    }

    public function testEnQuery()
    {
        $qb = $this->createQb();
        $qb->from()->document('Doctrine\Tests\Models\Translation\Article', 'a');
        $qb->setLocale('en');
        $res = $qb->getQuery()->execute();

        $this->assertEquals('Some interesting subject', $res->first()->topic);
    }

}
