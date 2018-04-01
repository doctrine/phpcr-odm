Using a Custom Document Class Mapper with PHPCR-ODM
===================================================

The default document class mapper of PHPCR-ODM uses the attribute
``phpcr:class`` to store and retrieve the document class of a node. When
accessing an existing PHPCR repository, you might need different logic to
decide on the class.

You can extend the ``DocumentClassMapper`` or implement
``DocumentClassMapperInterface`` from scratch. The important methods are
``getClassName`` that needs to find the class name and ``writeMetadata``
that needs to make sure the class of a newly stored document can be
determined when loading it again.

An example mapper from the `symfony cmf sandbox`_
(``magnolia_integration`` branch)::

    namespace Sandbox\MagnoliaBundle\Document;

    use Doctrine\ODM\PHPCR\DocumentClassMapper;
    use Doctrine\ODM\PHPCR\DocumentManager;

    use PHPCR\NodeInterface;
    use PHPCR\PropertyType;

    class MagnoliaDocumentClassMapper extends DocumentClassMapper
    {
        private $templateMap;

        /**
         * @param array $templateMap map from mgnl:template values to document class names
         */
        public function __construct($templateMap)
        {
            $this->templateMap = $templateMap;
        }

        /**
         * Determine the class name from a given node
         *
         * @param DocumentManager
         * @param NodeInterface $node
         * @param string $className
         *
         * @return string
         *
         * @throws \RuntimeException if no class name could be determined
         */
        public function getClassName(DocumentManager $dm, NodeInterface $node, $className = null)
        {
            $className = parent::getClassName($dm, $node, $className);
            if ('Doctrine\ODM\PHPCR\Document\Generic' == $className) {
                if ($node->hasNode('MetaData')) {
                    $metaData = $node->getNode('MetaData');
                    if ($metaData->hasProperty('mgnl:template')) {
                        if (isset($this->templateMap[$metaData->getPropertyValue('mgnl:template')])) {
                            return $this->templateMap[$metaData->getPropertyValue('mgnl:template')];
                        }
                    }
                }
            }

            return $className;
        }
    }

Then adjust your :ref:`bootstrap code <intro-bootstrap>` to use the
custom mapper::

    /* prepare the doctrine configuration */
    $config = new \Doctrine\ODM\PHPCR\Configuration();
    $map = array(
        'standard-templating-kit:pages/stkSection' => 'Sandbox\MagnoliaBundle\Document\Section',
    );
    $mapper = new MagnoliaDocumentClassMapper($map);
    $config->setDocumentClassMapper($mapper);

    $documentManager = \Doctrine\ODM\PHPCR\DocumentManager::create($session, $config);

    ...

Symfony2 integration
--------------------

If you are running on Symfony2, you do not instantiate PHPCR-ODM manually.
Instead, you adjust the configuration in your service definition.

Here we overwrite the ``doctrine.odm_configuration`` service to call
``setDocumentClassMapper`` on it. This will make it use this mapper instead
of instantiating the default one. An example from the `symfony cmf sandbox`_
(``magnolia_integration`` branch):

.. configuration-block::

    .. code-block:: yaml

        # if you want to overwrite default configuration, otherwise use a
        # custom name and specify in odm configuration block

        doctrine.odm_configuration:
            class: %doctrine_phpcr.odm.configuration.class%
            calls:
                - [ setDocumentClassMapper, [@sandbox_magnolia.odm_mapper] ]

        sandbox_magnolia.odm_mapper:
            class: "Sandbox\MagnoliaBundle\Document\MagnoliaDocumentClassMapper"
            arguments:
                - 'standard-templating-kit:pages/stkSection': 'Sandbox\MagnoliaBundle\Document\Section'

    .. code-block:: xml

        <service id="doctrine.odm_configuration"
            class="%doctrine_phpcr.odm.configuration.class%">
            <call method="setDocumentClassMapper">
                <argument type="service" id="sandbox_magnolia.odm_mapper" />
            </call>
        </service>

        <service id="sandbox_magnolia.odm_mapper"
            class="Sandbox\MagnoliaBundle\Document\MagnoliaDocumentClassMapper">
            <argument type="collection">
                <argument type="standard-templating-kit:pages/stkSection">Sandbox\MagnoliaBundle\Document\Section</argument>
            </argument>
        </service>

    .. code-block:: php

        use Symfony\Component\DependencyInjection\Definition;
        use Symfony\Component\DependencyInjection\Reference;

        $container
            ->register('doctrine.odm_configuration', '%doctrine_phpcr.odm.configuration.class%')
            ->addMethodCall('setDocumentClassMapper', array(
                new Reference('sandbox_magnolia.odm_mapper'),
            ))
        ;

        $container ->setDefinition('sandbox_amgnolia.odm_mapper', new Definition(
            'Sandbox\MagnoliaBundle\Document\MagnoliaDocumentClassMapper',
            array(
                array(
                    'standard-templating-kit:pages/stkSection' => 'Sandbox\MagnoliaBundle\Document\Section',
                ),
            ),
        ));

.. _`symfony cmf sandbox`: https://github.com/symfony-cmf/cmf-sandbox/tree/magnolia_integration
