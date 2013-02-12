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

use Doctrine\ODM\PHPCR\Mapping\MappingException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show information about mapped documents
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class InfoDoctrineCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('doctrine:phpcr:mapping:info')
            ->setDescription('Shows basic information about all mapped documents')
            ->setHelp(<<<EOT
The <info>doctrine:mapping:info</info> shows basic information about which
documents exist and possibly if their mapping information contains errors or
not.

<info>php app/console doctrine:phpcr:mapping:info</info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentManager = $this->getHelper('phpcr')->getDocumentManager();

        $documentClassNames = $documentManager->getConfiguration()
            ->getMetadataDriverImpl()
            ->getAllClassNames();

        if (!$documentClassNames) {
            throw new \LogicException(
                'You do not have any mapped Doctrine PHPCR ODM documents'
            );
        }

        $output->writeln(sprintf("Found <info>%d</info> documents mapped in document manager:", count($documentClassNames)));

        foreach ($documentClassNames as $documentClassName) {
            try {
                $output->writeln(sprintf("<info>[OK]</info>   %s", $documentClassName));
            } catch (MappingException $e) {
                $message = $e->getMessage();
                while ($e->getPrevious()) {
                    $e = $e->getPrevious();
                    $message .= "\n".$e->getMessage();
                }

                $output->writeln("<error>[FAIL]</error> ".$documentClassName);
                $output->writeln(sprintf("<comment>%s</comment>", $message));
                $output->writeln('');
            }
        }

        return 0;
    }
}
