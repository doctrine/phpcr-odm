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

use Doctrine\ODM\PHPCR\Tools\Helper\UniqueNodeTypeHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verify that any documents which are mapped as having unique
 * node types are truly unique.
 */
class VerifyUniqueNodeTypesMappingCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:phpcr:mapping:verify-unique-node-types')
            ->setDescription('Verify that documents claiming to have unique node types are truly unique')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command checks all mapped PHPCR-ODM documents
and verifies that any claiming to use unique node types are truly unique.
EOT
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentManager = $this->getHelper('phpcr')->getDocumentManager();
        $uniqueNodeTypeHelper = new UniqueNodeTypeHelper();

        $debugInformation = $uniqueNodeTypeHelper->checkNodeTypeMappings($documentManager);

        if (OutputInterface::VERBOSITY_DEBUG <= $output->getVerbosity()) {
            foreach ($debugInformation as $className => $debug) {
                $output->writeln(sprintf(
                    'The document <info>%s</info> uses %snode type <info>%s</info>',
                    $className,
                    $debug['unique_node_type'] ? '<comment>uniquely mapped</comment> ' : '',
                    $debug['node_type']
                ));
            }
        }

        return 0;
    }
}
