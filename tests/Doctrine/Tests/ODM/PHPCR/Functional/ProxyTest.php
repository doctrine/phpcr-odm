<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

/**
 * @group functional
 */
class ProxyTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    private $type;

    private $node;

    public function setUp()
    {
        $this->type = 'Doctrine\Tests\ODM\PHPCR\Functional\Article';

        $this->dm = $this->createDocumentManager();

        $session = $this->dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }
        $this->node = $root->addNode('functional');
        $article = $this->node->addNode('article');
        $article->setProperty('title', 'foo');
        $article->setProperty('body', 'bar');
        $article->setProperty('_doctrine_alias', 'article');
        $session->save();

        $cmf = $this->dm->getMetadataFactory();
        $metadata = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata($this->type);
        $metadata->setAlias('article');
        $metadata->mapProperty(array('fieldName' => 'title', 'type' => 'string'));
        $metadata->mapProperty(array('fieldName' => 'body', 'type' => 'string'));
        $cmf->setMetadataFor($this->type, $metadata);
    }

    public function testGetReference()
    {
        $proxy = $this->dm->getReference($this->type, '/functional/article');

        $this->assertType('Doctrine\ODM\PHPCR\Proxy\Proxy', $proxy);
        $this->assertFalse($proxy->__isInitialized__);

        $this->assertEquals('foo', $proxy->getTitle());
        $this->assertTrue($proxy->__isInitialized__);
        $this->assertEquals('bar', $proxy->getBody());
    }

    public function testProxyFactorySetsProxyMetadata()
    {
        $proxy = $this->dm->getReference($this->type, 1);

        $proxyClass = get_class($proxy);
        $this->assertTrue($this->dm->getMetadataFactory()->hasMetadataFor($proxyClass), "Proxy class '" . $proxyClass . "' should be registered as metadata.");
        $this->assertSame($this->dm->getClassMetadata($proxyClass), $this->dm->getClassMetadata($this->type), "Metadata instances of proxy class and real instance have to be the same.");
    }
}

class Article
{
    private $title;
    private $body;

    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getBody()
    {
        return $this->body;
    }
}
