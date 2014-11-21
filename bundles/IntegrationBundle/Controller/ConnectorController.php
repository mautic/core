<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;

/**
 * Class ConnectorController
 */
class ConnectorController extends FormController
{
    /**
     * @return HttpFoundation\JsonResponse|HttpFoundation\RedirectResponse|HttpFoundation\Response
     */
    public function indexAction()
    {
        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'integration:integrations:view',
            'integration:integrations:create',
            'integration:integrations:edit',
            'integration:integrations:delete'
        ), "RETURN_ARRAY");

        if (!$permissions['integration:integrations:view']) {
            return $this->accessDenied();
        }

        $networkObjects = NetworkIntegrationHelper::getNetworkObjects($this->factory, null, null, true);
        $connectors     = array();

        foreach ($networkObjects as $name => $object) {
            $connectors[] = $name;
        }

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'items'       => $connectors,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticIntegrationBundle:Connector:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_integration_connector_index',
                'mauticContent'  => 'connector',
                'route'          => $this->generateUrl('mautic_integration_connector_index')
            )
        ));
    }
}
