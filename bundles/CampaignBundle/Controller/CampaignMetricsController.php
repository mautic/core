<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\Chart\BarChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\StatRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CampaignMetricsController extends AbstractController
{
    public function __construct(
        private Translator $translator,
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public function emailWeekdaysAction(
        StatRepository $statRepository,
        CampaignModel $model,
        int $objectId,
        string $dateFrom = '',
        string $dateTo = '',
    ): Response {
        $entity               = $model->getEntity($objectId);
        $eventsIds            = $entity->getEmailSendEvents()->getKeys();
        $dateFromObject       = new \DateTimeImmutable($dateFrom);
        $dateToObject         = new \DateTimeImmutable($dateTo);

        $dateTimeHelper        = new DateTimeHelper();
        $defaultTimezoneOffset = $dateTimeHelper->getLocalDateTime()->format('Z');
        $stats                 = $statRepository->emailMetricsPerWeekdayByCampaignEvents($eventsIds, $dateFromObject, $dateToObject, $defaultTimezoneOffset);

        $chart  = new BarChart([
            $this->translator->trans('mautic.core.date.monday'),
            $this->translator->trans('mautic.core.date.tuesday'),
            $this->translator->trans('mautic.core.date.wednesday'),
            $this->translator->trans('mautic.core.date.thursday'),
            $this->translator->trans('mautic.core.date.friday'),
            $this->translator->trans('mautic.core.date.saturday'),
            $this->translator->trans('mautic.core.date.sunday'),
        ]);

        $chart->setDataset($this->translator->trans('mautic.email.sent'), array_column($stats, 'sent_count'));
        $chart->setDataset($this->translator->trans('mautic.email.read'), array_column($stats, 'read_count'));
        $chart->setDataset($this->translator->trans('mautic.email.click'), array_column($stats, 'hit_count'));

        return $this->render(
            '@MauticCore/Helper/chart.html.twig',
            [
                'chartData'   => $chart->render(),
                'chartType'   => 'bar',
                'chartHeight' => 300,
            ]
        );
    }

    public function emailHoursAction(
        StatRepository $statRepository,
        CampaignModel $model,
        int $objectId,
        string $dateFrom = '',
        string $dateTo = '',
    ): Response {
        $entity               = $model->getEntity($objectId);
        $eventsIds            = $entity->getEmailSendEvents()->getKeys();
        $dateFromObject       = new \DateTimeImmutable($dateFrom);
        $dateToObject         = new \DateTimeImmutable($dateTo);

        $dateTimeHelper        = new DateTimeHelper();
        $defaultTimezoneOffset = $dateTimeHelper->getLocalDateTime()->format('Z');

        $stats = $statRepository->emailMetricsPerHourByCampaignEvents($eventsIds, $dateFromObject, $dateToObject, $defaultTimezoneOffset);

        $hoursRange = range(0, 23, 1);
        $labels     = [];

        $timeFormat = $this->coreParametersHelper->get('date_format_timeonly');

        foreach ($hoursRange as $r) {
            $startTime = (new \DateTime())->setTime($r, 0);
            $endTime   = (new \DateTime())->setTime(($r + 1) % 24, 0);

            $labels[] = $startTime->format($timeFormat).' - '.$endTime->format($timeFormat);
        }

        $chart  = new BarChart($labels);
        $chart->setDataset($this->translator->trans('mautic.email.sent'), array_column($stats, 'sent_count'));
        $chart->setDataset($this->translator->trans('mautic.email.read'), array_column($stats, 'read_count'));
        $chart->setDataset($this->translator->trans('mautic.email.click'), array_column($stats, 'hit_count'));

        return $this->render(
            '@MauticCore/Helper/chart.html.twig',
            [
                'chartData'   => $chart->render(),
                'chartType'   => 'hour',
                'chartHeight' => 300,
            ]
        );
    }
}
