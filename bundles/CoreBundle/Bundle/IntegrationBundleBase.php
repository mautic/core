<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Bundle;

use Mautic\CoreBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Base Bundle class which should be extended by addon bundles
 */
abstract class IntegrationBundleBase extends Bundle
{
    /**
     * Checks if the bundle is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        $name   = str_replace('Mautic', '', $this->getName());
        $helper = new IntegrationHelper($this->container->get('mautic.factory'));

        return $helper->isEnabled($name);
    }
}
