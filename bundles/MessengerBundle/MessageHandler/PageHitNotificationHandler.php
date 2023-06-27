<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\MessageHandler;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\MessengerBundle\Exceptions\InvalidPayloadException;
use Mautic\MessengerBundle\Exceptions\MauticMessengerException;
use Mautic\MessengerBundle\Factory\MessengerRequestFactory;
use Mautic\MessengerBundle\MauticMessengerBundle;
use Mautic\MessengerBundle\Message\PageHitNotification;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\Entity\PageRepository;
use Mautic\PageBundle\Entity\RedirectRepository;
use Mautic\PageBundle\Model\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class PageHitNotificationHandler implements MessageSubscriberInterface
{
    public function __construct(private PageRepository $pageRepository, private HitRepository $hitRepository, private LeadRepository $leadRepository, private LoggerInterface $logger, private RedirectRepository $redirectRepository, private PageModel $pageModel)
    {
    }

    /** @throws MauticMessengerException */
    public function __invoke(PageHitNotification $message, Acknowledger $ack = null): void
    {
        try {
            $parsed = $this->parseMessage($message);
            $this->pageModel->processPageHit(...$parsed);
        } catch (InvalidPayloadException $payloadException) {
            $this->logger->error(MauticMessengerBundle::LOG_PREFIX.' Invalid payload #'.$message->getHitId().' '.$payloadException->getMessage());

            throw $payloadException;
        } catch (\Exception $exception) {
            $this->logger->error(MauticMessengerBundle::LOG_PREFIX.$exception->getMessage(), (array) $exception);
            throw new MauticMessengerException(MauticMessengerBundle::LOG_PREFIX.$exception->getMessage(), 400, $exception);
        }
        $this->logger->info(MauticMessengerBundle::LOG_PREFIX.'processed page hit #'.$message->getHitId());
    }

    /** @return iterable<string, mixed> */
    public static function getHandledMessages(): iterable
    {
        yield PageHitNotification::class => [];
    }

    /**
     * @return array<string, mixed>
     * @throws InvalidPayloadException
     */
    private function parseMessage(PageHitNotification $message): array
    {
        $hit = $message->getHitId() > 0 ? $this->hitRepository->find($message->getHitId()) : null;

        $pageObject = null;
        if (null !== $message->getPageId()) {
            try {
                $pageObject = $message->isRedirect()
                    ? $this->redirectRepository->find($message->getPageId())
                    : $this->pageRepository->find($message->getPageId());
            } catch (\Exception $exception) {
                $this->logger->error(
                    sprintf('%sInvalid page/redirect, exception. #%s', MauticMessengerBundle::LOG_PREFIX, $message->getPageId()), ['message' => $message]);
                throw $exception;
            }

            if (null === $pageObject) {
                $this->logger->error(
                    sprintf('%sInvalid page/redirect, id not found. #%s', MauticMessengerBundle::LOG_PREFIX, $message->getPageId())
                );
                throw new InvalidPayloadException('Missing required information', ['message' => $message]);
            }
        }

        if (!$hit instanceof Hit && $message->getHitId() > 0) {
            $this->logger->warning('Invalid hit id #'.$message->getHitId(), ['message' => $message]);

            throw new InvalidPayloadException('Invalid hit id #'.$message->getHitId(), (array) $message);
        }

        //  Lead IS mandatory field
        if (null === $lead = $this->leadRepository->find($message->getLeadId())) {
            $this->logger->error('Invalid lead id #'.$message->getLeadId(), ['message' => $message]);

            throw new InvalidPayloadException('Invalid lead id', (array) $message);
        }

        return [
            'hit'                    => $hit,
            'page'                   => $pageObject,
            'request'                => MessengerRequestFactory::fromArray($message->getRequest()),
            'lead'                   => $lead,
            'trackingNewlyGenerated' => $message->isNew(),
            'activeRequest'          => false,
            'hitDate'                => new \DateTime((new DateTimeHelper($message->getEventTime(), 'c'))->toLocalString()),
        ];
    }
}
