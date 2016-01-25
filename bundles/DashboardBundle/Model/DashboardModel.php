<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class DashboardModel
 */
class DashboardModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticDashboardBundle:Widget');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'dashboard:widgets';
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Widget();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Load widgets for the current user from database
     *
     * @return array
     */
    public function getWidgets()
    {
        $widgets = $this->getEntities(array(
            'orderBy' => 'w.ordering',
            'filter' => array(
                'force' => array(
                    array(
                        'column' => 'w.createdBy',
                        'expr'   => 'eq',
                        'value'  => $this->factory->getUser()->getId()
                    )
                )
            )
        ));

        return $widgets;
    }

    /**
     * Fill widgets with their content
     *
     * @param array $widgets
     */
    public function populateWidgetsContent(&$widgets)
    {
        if (count($widgets)) {
            foreach ($widgets as &$widget) {
                if (!($widget instanceof Widget)) {
                    $widget = $this->populateWidgetEntity($widget);
                }
                $this->populateWidgetContent($widget);
            }
        }
    }

    /**
     * Creates a new Widget object from an array data
     *
     * @param array $data
     *
     * @return Widget
     */
    public function populateWidgetEntity($data)
    {
        $entity = new Widget;

        foreach ($data as $property => $value) {
            $method = "set".ucfirst($property);
            if (method_exists($entity, $method)) {
                $entity->$method($value);
            }
            unset($data[$property]);
        }

        return $entity;
    }

    /**
     * Load widget content from the onWidgetDetailGenerate event
     *
     * @return array
     */
    public function populateWidgetContent(Widget &$widget)
    {
        $cacheDir   = $this->factory->getParameter('cached_data_dir', $this->factory->getSystemPath('cache', true));
        $dispatcher = $this->factory->getDispatcher();

        if ($widget->getCacheTimeout() == null || $widget->getCacheTimeout() == -1) {
            $widget->setCacheTimeout($this->factory->getParameter('cached_data_timeout'));
        }

        $event = new WidgetDetailEvent($this->translator);
        $event->setWidget($widget);
        $event->setCacheDir($cacheDir);
        $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE, $event);
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead                                $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string|null                         $action
     * @param array                               $options
     *
     * @return \Symfony\Component\Form\Form
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Widget) {
            throw new MethodNotAllowedHttpException(array('Widget'), 'Entity must be of class Widget()');
        }

        if (!empty($action))  {
            $options['action'] = $action;
        }

        return $formFactory->create('widget', $entity, $options);
    }
}
