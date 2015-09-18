<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Doctrine\ODM\PHPCR\Translation\LocaleChooser\LocaleChooser;
use Doctrine\Tests\Models\Translation\Article;
use PHPCR\PropertyType;
use Doctrine\Tests\Models\CMS\CmsTeamUser;

/**
 * @group functional
 */
class RenameChildTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var \Doctrine\ODM\PHPCR\DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->dm->setLocaleChooserStrategy(new LocaleChooser(array('en' => array('fr'), 'fr' => array('en')), 'en'));
        $this->node = $this->resetFunctionalNode($this->dm);

        $article = new Article();
        $article->id = '/functional/parent';
        $article->topic = 'Test';
        $article->text = 'Can children be renamed?';

        $child = new Article();
        $child->parent = $article;
        $child->nodename = 'test';
        $child->topic = 'Test';
        $child->text = 'Can this node be renamed?';

        $this->dm->persist($article);
        $this->dm->persist($child);

        $this->dm->flush();
    }

    public function testRenameWithOneChild()
    {
        $this->dm->clear();

        $child = $this->dm->find(null, '/functional/parent/test');
        $this->assertEquals('test', $child->nodename);

        $child->nodename = 'renamed';
        $this->dm->flush();

        $renamed = $this->dm->find(null, '/function/parent/renamed');

        $this->assertNotNull($renamed);
        $this->assertEquals('Can this node be renamed?', $renamed->text);
    }

    public function testRenameWithParentChange()
    {
        $this->dm->clear();

        $child = $this->dm->find(null, '/functional/parent/test');
        $this->assertEquals('test', $child->nodename);

        $child->nodename = 'renamed';

        $parent = $child->parent;
        $parent->topic = 'Changed Test';

        $this->dm->flush();

        $renamed = $this->dm->find(null, '/function/parent/renamed');

        $this->assertNotNull($renamed);
        $this->assertEquals('Can this node be renamed?', $renamed->text);
    }

    public function testRenameWithTwoChildren()
    {
        $this->dm->clear();

        $parent = $this->dm->find(null, '/functional/parent');

        $secondChild = new Article();

        $secondChild->parent = $parent;
        $secondChild->nodename = 'test2';

        $secondChild->topic = 'Test';
        $secondChild->text = 'Just to show the difference';

        $this->dm->persist($secondChild);
        $this->dm->flush();

        $this->dm->clear();

        $child = $this->dm->find(null, '/functional/parent/test');
        $this->assertEquals('test', $child->nodename);

        $child->nodename = 'renamed';
        $this->dm->flush();

        $renamed = $this->dm->find(null, '/function/parent/renamed');

        $this->assertNotNull($renamed);
        $this->assertEquals('Can this node be renamed?', $renamed->text);
    }
}
