<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\UrlHelper;
use Symfony\Component\Templating\Helper\Helper;

class GravatarHelper extends Helper
{
    private static $devMode;

    public function __construct(MauticFactory $factory)
    {
        self::$devMode = $factory->getEnvironment() == 'dev';
    }

    static public function getImage($email, $size = '250', $default = null)
    {
        $localDefault     = 'assets/images/avatar.png';
        $url              = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?s='.$size;

        if ($default !== false && !self::$devMode) {
            if ($default === null) {
                $default = $localDefault;
            }

            $default = (strpos($default, '.') !== false && strpos($default, 'http') !== 0) ? UrlHelper::rel2abs($default) : $default;
            $url    .= '&d=' . urlencode($default);
        }

        return $url;
    }

    public function getName()
    {
        return 'gravatar';
    }
}