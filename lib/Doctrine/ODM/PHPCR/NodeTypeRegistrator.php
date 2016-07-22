<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

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
CND
        ;

        $nodeTypeManager = $session->getWorkspace()->getNodeTypeManager();
        $nodeTypeManager->registerNodeTypesCnd($cnd, true);
    }
}
