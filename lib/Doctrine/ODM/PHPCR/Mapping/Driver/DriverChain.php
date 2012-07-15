<?php

namespace Doctrine\ODM\PHPCR\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;

/**
 * The DriverChain allows you to add multiple other mapping drivers for
 * certain namespaces
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @deprecated  please use \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain instead
 */
class DriverChain extends MappingDriverChain
{
}
