<?php

namespace Mautic\CampaignBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventLogModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\EmailBundle\Entity\StatRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class AjaxController extends CommonAjaxController
{
    public function __construct(
        private DateHelper $dateHelper,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function updateConnectionsAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $session        = $request->getSession();
        $campaignId     = InputHelper::clean($request->query->get('campaignId'));
        $canvasSettings = $request->request->all()['canvasSettings'] ?? [];
        if (empty($campaignId)) {
            $dataArray = ['success' => 0];
        } else {
            $session->set('mautic.campaign.'.$campaignId.'.events.canvassettings', $canvasSettings);

            $dataArray = ['success' => 1];
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function updateScheduledCampaignEventAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $eventId      = (int) $request->request->get('eventId');
        $contactId    = (int) $request->request->get('contactId');
        $newDate      = InputHelper::clean($request->request->get('date'));
        $originalDate = InputHelper::clean($request->request->get('originalDate'));

        $dataArray = ['success' => 0, 'date' => $originalDate];

        if (!empty($eventId) && !empty($contactId) && !empty($newDate)) {
            if ($log = $this->getContactEventLog($eventId, $contactId)) {
                $newDate = new \DateTime($newDate);

                if ($newDate >= new \DateTime()) {
                    $log->setTriggerDate($newDate);

                    /** @var EventLogModel $logModel */
                    $logModel = $this->getModel('campaign.event_log');
                    $logModel->saveEntity($log);

                    $dataArray = [
                        'success' => 1,
                        'date'    => $newDate->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        // Format the date to match the view
        $dataArray['formattedDate'] = $this->dateHelper->toFull($dataArray['date']);

        return $this->sendJsonResponse($dataArray);
    }

    public function cancelScheduledCampaignEventAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $dataArray = ['success' => 0];

        $eventId   = (int) $request->request->get('eventId');
        $contactId = (int) $request->request->get('contactId');
        if (!empty($eventId) && !empty($contactId)) {
            if ($log = $this->getContactEventLog($eventId, $contactId)) {
                $log->setIsScheduled(false);

                /** @var EventLogModel $logModel */
                $logModel           = $this->getModel('campaign.event_log');
                $metadata           = $log->getMetadata();
                $metadata['errors'] = $this->translator->trans(
                    'mautic.campaign.event.cancelled.time',
                    ['%date%' => $log->getTriggerDate()->format('Y-m-d H:i:s')]
                );
                $log->setMetadata($metadata);
                $logModel->getRepository()->saveEntity($log);

                $dataArray = ['success' => 1];
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return LeadEventLog|null
     */
    protected function getContactEventLog($eventId, $contactId)
    {
        $contact = $this->getModel('lead')->getEntity($contactId);
        if ($contact) {
            if ($this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $contact->getPermissionUser())) {
                /** @var EventLogModel $logModel */
                $logModel = $this->getModel('campaign.event_log');

                /** @var LeadEventLog $log */
                $log = $logModel->getRepository()
                                ->findOneBy(
                                    [
                                        'lead'  => $contactId,
                                        'event' => $eventId,
                                    ],
                                    ['dateTriggered' => 'desc']
                                );

                if ($log && ($log->getTriggerDate() > new \DateTime())) {
                    return $log;
                }
            }
        }

        return null;
    }

    /**
     * @return array{}|array<string, array<int, array<string, array<int, string>|bool|string>|string>>
     *
     * @throws \Exception
     */
    public function getCampaignOpeningTrendStats(Campaign $campaign, string $dateFrom, string $dateTo, array $eventsIds = []): array
    {
        $stats                = [];
        $eventsIds            = empty($eventsIds) ? $campaign->getEmailSendEvents()->getKeys() : $eventsIds;

        $dateFromObject = new \DateTimeImmutable($dateFrom);
        $dateToObject   = new \DateTimeImmutable($dateTo);

        $stats['days']  = $this->getEmailDaysData($eventsIds, $dateFromObject, $dateToObject);
        $stats['hours'] = $this->getEmailHoursData($eventsIds, $dateFromObject, $dateToObject);

        return $stats;
    }

    //public function openingTrendAction(
    //    StatRepository $statRepository,
    //    CampaignModel $model,
    //    int $objectId,
    //    string $dateFrom = '',
    //    string $dateTo = '',
    //): Response {
    //    $entity              = $model->getEntity($objectId);
    //    $eventsEmailsSend    = $entity->getEmailSendEvents();
    //    $emails              = [];
    //
    //    foreach ($eventsEmailsSend as $event) {
    //        $emails[$event->getId()] = [
    //            'eventId' => $event->getId(),
    //            'emailId' => $event->getChannelId(),
    //            'name'    => $event->getName().' (id:'.$event->getChannelId().')',
    //        ];
    //    }
    //
    //    $eventsIds            = empty($eventsIds) ? $entity->getEmailSendEvents()->getKeys() : $eventsIds;
    //    $stats                = [];
    //
    //    $dateFromObject = new \DateTimeImmutable($dateFrom);
    //    $dateToObject   = new \DateTimeImmutable($dateTo);
    //
    //    $stats['days']  = $this->getEmailDaysData($statRepository, $eventsIds, $dateFromObject, $dateToObject);
    //    $stats['hours'] = $this->getEmailHoursData($statRepository, $eventsIds, $dateFromObject, $dateToObject);
    //
    //    return new \Symfony\Component\HttpFoundation\JsonResponse($stats);
    //}

    public function openingTrendAction(
        StatRepository $statRepository,
        CampaignModel $model,
        int $objectId,
        string $dateFrom = '',
        string $dateTo = '',
    ): Response {
        $entity              = $model->getEntity($objectId);
        $eventsEmailsSend    = $entity->getEmailSendEvents();
        $emails              = [];

        foreach ($eventsEmailsSend as $event) {
            $emails[$event->getId()] = [
                'eventId' => $event->getId(),
                'emailId' => $event->getChannelId(),
                'name'    => $event->getName().' (id:'.$event->getChannelId().')',
            ];
        }

        $eventsIds            = empty($eventsIds) ? $entity->getEmailSendEvents()->getKeys() : $eventsIds;
        $dateFromObject = new \DateTimeImmutable($dateFrom);
        $dateToObject   = new \DateTimeImmutable($dateTo);

        $days  = $this->getEmailDaysData($statRepository, $eventsIds, $dateFromObject, $dateToObject);
        $hours = $this->getEmailHoursData($statRepository, $eventsIds, $dateFromObject, $dateToObject);

        return $this->render(
            '@MauticCore/Helper/opening_trend.html.twig',
            [
                'days'      => $days,
                'hours'     => $hours,
            ]
        );
    }

    /**
     * @return array<string, array<int, array<string, array<int, string>|bool|string>|string>>
     *
     * @throws \Exception
     */
    protected function getEmailDaysData(StatRepository $statRepository, array $eventsIds, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        $dateTimeHelper        = new DateTimeHelper();
        $defaultTimezoneOffset = $dateTimeHelper->getLocalTimezoneOffset('Z');
        $stats       = $statRepository->getEmailDayStats($eventsIds, $dateFrom, $dateTo, $defaultTimezoneOffset);

        $chart  = new LineChart();
        $chart->setLabels([
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

        return $chart->render();
    }

    /**
     * @return array<string, array<int, array<string, array<int, string>|bool|string>|string>>
     *
     * @throws \Exception
     */
    public function getEmailHoursData(StatRepository $statRepository, array $eventsIds, \DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        $dateTimeHelper        = new DateTimeHelper();
        $defaultTimezoneOffset = $dateTimeHelper->getLocalTimezoneOffset('Z');

        $stats = $statRepository->getEmailTimeStats($eventsIds, $dateFrom, $dateTo, $defaultTimezoneOffset);

        $hoursRange = range(0, 23, 1);
        $labels     = [];

        foreach ($hoursRange as $r) {
            $labels[] = sprintf('%02d:00', $r).'-'.sprintf('%02d:00', fmod($r + 1, 24));
        }

        $chart  = new LineChart();
        $chart->setLabels($labels);
        $chart->setDataset($this->translator->trans('mautic.email.sent'), array_column($stats, 'sent_count'));
        $chart->setDataset($this->translator->trans('mautic.email.read'), array_column($stats, 'read_count'));
        $chart->setDataset($this->translator->trans('mautic.email.click'), array_column($stats, 'hit_count'));

        return $chart->render();
    }
}
