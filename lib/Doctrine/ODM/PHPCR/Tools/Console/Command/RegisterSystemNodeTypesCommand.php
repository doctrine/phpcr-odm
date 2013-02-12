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

namespace Doctrine\ODM\PHPCR\Tools\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use PHPCR\Util\Console\Command\RegisterNodeTypesCommand;

use Doctrine\ODM\PHPCR\Translation\Translation;

/**
 * Command to register the phcpr-odm required node types.
 *
 * This command registers the necessary node types to get phpcr odm working
 */
class RegisterSystemNodeTypesCommand extends RegisterNodeTypesCommand
{
    private $phpcrNamespace = 'phpcr';
    private $phpcrNamespaceUri = 'http://www.doctrine-project.org/projects/phpcr_odm';
    private $localeNamespace = Translation::LOCALE_NAMESPACE;
    private $localeNamespaceUri = Translation::LOCALE_NAMESPACE_URI;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
        ->setName('doctrine:phpcr:register-system-node-types')
        ->setDescription('Register system node types in the PHPCR repository')
        ->setHelp(<<<EOT
Register system node types in the PHPCR repository.

This command registers the node types necessary for the ODM to work.
EOT
        );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $session \PHPCR\SessionInterface */
        $session = $this->getHelper('phpcr')->getSession();
        if ($session instanceof \Jackalope\Session
            && $session->getTransport() instanceof \Jackalope\Transport\Jackrabbit\Client
        ) {
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

            try {
                // automatically overwrite - we are inside our phpcr namespace, nothing can go wrong
                $this->updateFromCnd($input, $output, $session, $cnd, true);
            } catch (\Exception $e) {
                $output->writeln('<error>'.$e->getMessage().'</error>');

                return 1;
            }
        } else {
            $this->registerSystemNodeTypes($session);
        }
        $output->write(PHP_EOL.sprintf('Successfully registered system node types.') . PHP_EOL);

        return 0;
    }

    public function registerSystemNodeTypes($session)
    {
        $ns = $session->getWorkspace()->getNamespaceRegistry();
        $ns->registerNamespace($this->phpcrNamespace, $this->phpcrNamespaceUri);
        $ns->registerNamespace($this->localeNamespace, $this->localeNamespaceUri);
        $nt = $session->getWorkspace()->getNodeTypeManager();

        $phpcrClassTpl = $nt->createPropertyDefinitionTemplate();
        $phpcrClassTpl->setName('phpcr:class');
        $phpcrClassTpl->setRequiredType(\PHPCR\PropertyType::STRING);

        $phpcrClassParentsTpl = $nt->createPropertyDefinitionTemplate();
        $phpcrClassParentsTpl->setName('phpcr:classparents');
        $phpcrClassParentsTpl->setRequiredType(\PHPCR\PropertyType::STRING);
        $phpcrClassParentsTpl->setMultiple(true);

        $tpl = $nt->createNodeTypeTemplate();
        $tpl->setName('phpcr:managed');
        $tpl->setMixin(true);

        $props = $tpl->getPropertyDefinitionTemplates();
        $props->offsetSet(1, $phpcrClassTpl);
        $props->offsetSet(2, $phpcrClassParentsTpl);

        $nt->registerNodeType($tpl, true);
    }
}
