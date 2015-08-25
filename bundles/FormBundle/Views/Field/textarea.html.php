<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

echo $view->render(
    'MauticFormBundle:Field:text.html.php',
    array(
        'field'          => $field,
        'inForm'         => (isset($inForm)) ? $inForm : false,
        'type'           => 'textarea',
        'inputClass'     => 'textarea',
        'containerClass' => 'text',
        'id'             => $id,
        'deleted'        => (!empty($deleted)) ? true : false,
        'formId'         => (isset($formId)) ? $formId : 0,
        'formName'       => (isset($formName)) ? $formName : ''
    )
);
