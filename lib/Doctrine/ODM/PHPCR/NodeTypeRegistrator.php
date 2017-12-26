<?php

namespace Doctrine\ODM\PHPCR;

use Doctrine\ODM\PHPCR\Translation\Translation;
use PHPCR\SessionInterface;

/**
 * Encapsulates the logic for registering system node types.
 */
final class NodeTypeRegistrator
{
    private $phpcrNamespace = 'phpcr';
    private $phpcrNamespaceUri = 'http://www.doctrine-project.org/projects/phpcr_odm';
    private $localeNamespace = Translation::LOCALE_NAMESPACE;
    private $localeNamespaceUri = Translation::LOCALE_NAMESPACE_URI;

    /**
     * Register the system node types on the given session.
     *
     * @param SessionInterface
     */
    public function registerNodeTypes(SessionInterface $session)
    {
        $cnd = <<<CND
// register phpcr_locale namespace
<$this->localeNamespace='$this->localeNamespaceUri'>
// register phpcr namespace
<$this->phpcrNamespace='$this->phpcrNamespaceUri'>
[phpcr:managed]
mixin
- phpcr:class (STRING)
- phpcr:classparents (STRING) multiple
CND;

        $nodeTypeManager = $session->getWorkspace()->getNodeTypeManager();
        $nodeTypeManager->registerNodeTypesCnd($cnd, true);
    }
}
