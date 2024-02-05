<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CampaignBundle\Model\EventModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventModelTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    private $entityManagerMock;

    /**
     * @var EventRepository|MockObject
     */
    private $eventRepositoryMock;

    /**
     * @var EventModel
     */
    private \PHPUnit\Framework\MockObject\MockObject $eventModel;

    protected function setUp(): void
    {
        $this->entityManagerMock   = $this->createMock(EntityManager::class);
        $this->eventRepositoryMock = $this->createMock(EventRepository::class);
        $this->eventModel          = new EventModel();
    }

    public function testThatClonedEventsDoNotAttemptNullingParentInDeleteEvents(): void
    {
        $this->entityManagerMock->expects($this->never())
            ->method('getRepository')
            ->with(Event::class)
            ->willReturn($this->eventRepositoryMock);

        $currentEvents = [
            'new1',
            'new2',
            'new3',
        ];

        $deletedEvents = [
            'new1',
        ];

        $this->eventModel->setEntityManager($this->entityManagerMock);
        $this->eventModel->deleteEvents($currentEvents, $deletedEvents);
    }

    public function testThatItDeletesEventLogs(): void
    {
        $idToDelete = 'old1';

        $currentEvents = [
            'new1',
        ];

        $deletedEvents = [
            'new1',
            $idToDelete,
        ];

        $this->entityManagerMock->method('getRepository')
            ->with(Event::class)
            ->willReturn($this->eventRepositoryMock);

        $this->eventRepositoryMock->expects($this->once())
            ->method('nullEventRelationships')
            ->with([$idToDelete]);

        $this->eventRepositoryMock->expects($this->once())
            ->method('setEventsAsDeleted')
            ->with([1 => $idToDelete]);

        $dispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(CampaignEvents::ON_EVENT_DELETE, new DeleteEvent([$idToDelete]));

        $this->eventModel->setEntityManager($this->entityManagerMock);
        $this->eventModel->setDispatcher($dispatcherMock);
        $this->eventModel->deleteEvents($currentEvents, $deletedEvents);
    }

    public function testDeleteEventsByCampaignId(): void
    {
        /** @var EventModel&MockObject */
        $mockModel = $this->getMockBuilder(EventModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository', 'deleteEventsByEventIds'])
            ->getMock();

        $mockModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->eventRepositoryMock);

        $campaignEvents = ['1', '2', '3'];

        $this->eventRepositoryMock->expects($this->once())
            ->method('getCampaignEventIds')
            ->with(1)
            ->willReturn($campaignEvents);

        $mockModel->expects($this->once())->method('deleteEventsByEventIds')
            ->with($campaignEvents);

        $mockModel->deleteEventsByCampaignId(1);
    }
}
