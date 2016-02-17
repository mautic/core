<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PageBundle\EventListener;

use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class DashboardSubscriber
 *
 * @package Mautic\PageBundle\EventListener
 */
class DashboardSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s)
     *
     * @var string
     */
    protected $bundle = 'page';

    /**
     * Define the widget(s)
     *
     * @var string
     */
    protected $types = array(
        'page.hits.in.time' => array(
            'formAlias' => 'lead_dashboard_leads_in_time_widget'
        ),
        'unique.vs.returning.leads' => array(
            'formAlias' => null
        ),
        'dwell.times' => array(
            'formAlias' => null
        ),
        'popular.pages' => array(
            'formAlias' => null
        )
    );

    /**
     * Set a widget detail when needed 
     *
     * @param WidgetDetailEvent $event
     *
     * @return void
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event)
    {
        if ($event->getType() == 'page.hits.in.time') {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            // Make sure the params exist
            if (empty($params['amount']) || empty($params['timeUnit'])) {
                $event->setErrorMessage('mautic.core.configuration.value.not.set');
            } else {
                if (!$event->isCached()) {
                    $model = $this->factory->getModel('page');
                    $event->setTemplateData(array(
                        'chartType'   => 'line',
                        'chartHeight' => $widget->getHeight() - 80,
                        'chartData'   => $model->getHitsLineChartData($params['amount'], $params['timeUnit'], $params['dateFrom'], $params['dateTo'])
                    ));
                }
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'unique.vs.returning.leads') {
            if (!$event->isCached()) {
                $model = $this->factory->getModel('page');
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $model->getNewVsReturningPieChartData()
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'dwell.times') {
            if (!$event->isCached()) {
                $model = $this->factory->getModel('page');
                $event->setTemplateData(array(
                    'chartType'   => 'pie',
                    'chartHeight' => $event->getWidget()->getHeight() - 80,
                    'chartData'   => $model->getDwellTimesPieChartData()
                ));
            }

            $event->setTemplate('MauticCoreBundle:Helper:chart.html.php');
            $event->stopPropagation();
        }

        if ($event->getType() == 'popular.pages') {
            if (!$event->isCached()) {
                $repo = $this->factory->getModel('page')->getRepository();

                // Count the pages limit from the widget height
                $limit = round((($event->getWidget()->getHeight() - 80) / 35) - 1);
                $pages = $repo->getPopularPages($limit);
                $items = array();

                // Build table rows with links
                if ($pages) {
                    foreach ($pages as &$page) {
                        $pageUrl = $this->factory->getRouter()->generate('mautic_page_action', array('objectAction' => 'view', 'objectId' => $page['id']));
                        $row = array(
                            $pageUrl => $page['title'],
                            $page['hits']
                        );
                        $items[] = $row;
                    }
                }

                $event->setTemplateData(array(
                    'headItems'   => array(
                        $event->getTranslator()->trans('mautic.dashboard.label.title'),
                        $event->getTranslator()->trans('mautic.dashboard.label.hits')
                    ),
                    'bodyItems'   => $items
                ));
            }
            
            $event->setTemplate('MauticCoreBundle:Helper:table.html.php');
            $event->stopPropagation();
        }
    }
}
