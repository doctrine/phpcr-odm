<?php

namespace Doctrine\Tests\ODM\PHPCR;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\PHPCR\Configuration;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\Mapping\Driver\AttributeDriver;
use Jackalope\RepositoryFactoryDoctrineDBAL;
use Jackalope\RepositoryFactoryJackrabbit;
use PHPCR\NodeInterface;
use PHPCR\RepositoryFactoryInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;
use PHPUnit\Framework\TestCase;

abstract class PHPCRFunctionalTestCase extends TestCase
{
    /**
     * @var SessionInterface[]
     */
    private $sessions = [];

    public function createDocumentManager(array $paths = null, bool $annotations = false): DocumentManager
    {
        AnnotationReader::addGlobalIgnoredName('group');

        if (empty($paths)) {
            $paths = [__DIR__.'/../../Models'];
        }

        $metaDriver = $annotations
            ? new AnnotationDriver(new AnnotationReader(), $paths)
            : new AttributeDriver($paths)
        ;

        $factoryclass = $GLOBALS['DOCTRINE_PHPCR_FACTORY'] ?? RepositoryFactoryJackrabbit::class;

        if (RepositoryFactoryDoctrineDBAL::class === ltrim($factoryclass, '\\')) {
            $params = [];
            foreach ($GLOBALS as $key => $value) {
                if (str_starts_with($key, 'jackalope.doctrine.dbal.')) {
                    $params[substr($key, strlen('jackalope.doctrine.dbal.'))] = $value;
                }
            }
            if (array_key_exists('username', $params)) {
                $params['user'] = $params['username'];
            }
            $GLOBALS['jackalope.doctrine_dbal_connection'] = DriverManager::getConnection($params);
        }

        /** @var RepositoryFactoryInterface $factory */
        $factory = new $factoryclass();
        $parameters = array_intersect_key($GLOBALS, $factory->getConfigurationKeys());

        // factory returns null if it gets unknown parameters
        $repository = $factory->getRepository($parameters);
        $this->assertNotNull($repository, 'There is an issue with your parameters: '.var_export(array_keys($parameters), true));

        $workspace = $GLOBALS['DOCTRINE_PHPCR_WORKSPACE'] ?? 'tests';

        $user = $GLOBALS['DOCTRINE_PHPCR_USER'] ?? '';
        $pass = $GLOBALS['DOCTRINE_PHPCR_PASS'] ?? '';

        $credentials = new SimpleCredentials($user, $pass);
        $session = $repository->login($credentials, $workspace);
        $this->sessions[] = $session;

        $config = new Configuration();
        $config->setMetadataDriverImpl($metaDriver);

        return DocumentManager::create($session, $config);
    }

    public function resetFunctionalNode(DocumentManager $dm): NodeInterface
    {
        $session = $dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }

        $node = $root->addNode('functional');
        $session->save();

        $dm->clear();

        return $node;
    }

    public function tearDown(): void
    {
        foreach ($this->sessions as $session) {
            $session->logout();
        }
        $this->sessions = [];
    }
}
