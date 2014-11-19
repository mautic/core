<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Class ExceptionController
 */
class ExceptionController extends \Symfony\Bundle\TwigBundle\Controller\ExceptionController
{
    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $code = $exception->getStatusCode();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(array(
                'error' => array(
                    'code'      => $code,
                    'text'      => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                    'exception' => $exception->getMessage(),
                    'trace'     => ($this->debug) ? $exception->getTrace() : ''
                )
            ), $code);
        }

        return parent::showAction($request, $exception, $logger);
    }
}
