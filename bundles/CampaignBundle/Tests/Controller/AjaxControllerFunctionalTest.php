<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Tests\Functional\Fixtures\FixtureHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Tests\Functional\Fixtures\EmailFixturesHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    private FixtureHelper $campaignFixturesHelper;
    private EmailFixturesHelper $emailFixturesHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignFixturesHelper = new FixtureHelper($this->em);
        $this->emailFixturesHelper    = new EmailFixturesHelper($this->em);
    }

    public function testCancelScheduledCampaignEventAction(): void
    {
        $this->campaignFixturesHelper = new FixtureHelper($this->em);
        $contact                      = $this->campaignFixturesHelper->createContact('some@contact.email');
        $campaign                     = $this->campaignFixturesHelper->createCampaign('Scheduled event test');
        $this->campaignFixturesHelper->addContactToCampaign($contact, $campaign);
        $this->campaignFixturesHelper->createCampaignWithScheduledEvent($campaign);
        $this->em->flush();

        $commandResult = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);

        Assert::assertStringContainsString('1 total event was scheduled', $commandResult->getDisplay());

        $payload = [
            'action'    => 'campaign:cancelScheduledCampaignEvent',
            'eventId'   => $campaign->getEvents()[0]->getId(),
            'contactId' => $contact->getId(),
        ];

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/ajax', $payload);

        // Ensure we'll fetch fresh data from the database and not from entity manager.
        $this->em->detach($contact);
        $this->em->detach($campaign);

        /** @var LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->em->getRepository(LeadEventLog::class);

        /** @var LeadEventLog $log */
        $log = $leadEventLogRepository->findOneBy(['lead' => $contact, 'campaign' => $campaign]);

        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertSame('{"success":1}', $this->client->getResponse()->getContent());
        Assert::assertFalse($log->getIsScheduled());
    }

    public function testMetricsAction(): void
    {
        $contacts = [
            $this->campaignFixturesHelper->createContact('john@example.com'),
            $this->campaignFixturesHelper->createContact('paul@example.com'),
        ];

        $email = $this->emailFixturesHelper->createEmail('Test Email');
        $this->em->flush();

        $campaign      = $this->campaignFixturesHelper->createCampaignWithEmailSent($email->getId());
        $this->campaignFixturesHelper->addContactToCampaign($contacts[0], $campaign);
        $this->campaignFixturesHelper->addContactToCampaign($contacts[1], $campaign);
        $eventId = $campaign->getEmailSendEvents()->first()->getId();

        $emailStats = [
            $this->emailFixturesHelper->emulateEmailSend($contacts[0], $email, '2024-12-10 12:00:00', 'campaign.event', $eventId),
            $this->emailFixturesHelper->emulateEmailSend($contacts[1], $email, '2024-12-10 12:00:00', 'campaign.event', $eventId),
        ];

        $this->emailFixturesHelper->emulateEmailRead($emailStats[0], $email, '2024-12-10 12:09:00');
        $this->emailFixturesHelper->emulateEmailRead($emailStats[1], $email, '2024-12-11 21:35:00');

        $this->em->flush();
        $this->em->persist($email);

        $emailLinks = [
            $this->emailFixturesHelper->createEmailLink('https://example.com/1', $email->getId()),
            $this->emailFixturesHelper->createEmailLink('https://example.com/2', $email->getId()),
        ];
        $this->em->flush();

        $this->emailFixturesHelper->emulateLinkClick($email, $emailLinks[0], $contacts[0], '2024-12-10 12:10:00', 3);
        $this->emailFixturesHelper->emulateLinkClick($email, $emailLinks[1], $contacts[0], '2024-12-10 13:20:00');
        $this->emailFixturesHelper->emulateLinkClick($email, $emailLinks[1], $contacts[1], '2024-12-11 21:37:00');
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, "/s/campaign-email-opening-trend/{$campaign->getId()}/2024-12-01/2024-12-12");
        Assert::assertTrue($this->client->getResponse()->isOk());
        $content = $this->client->getResponse()->getContent();

        $crawler   = new Crawler($content);
        $daysJson  = $crawler->filter('canvas.bar-chart')->text(null, false);
        $hourJson  = $crawler->filter('canvas.hour-chart')->text(null, false);
        $daysData  = json_decode(html_entity_decode($daysJson), true);
        $hoursData = json_decode(html_entity_decode($hourJson), true);

        $daysDatasets = $daysData['datasets'];
        Assert::assertIsArray($daysDatasets);
        Assert::assertCount(3, $daysDatasets);  // Assuming there are 3 datasets: Email sent, Email read, Email clicked

        $expectedDaysLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $expectedDaysData   = [
            ['label' => 'Email sent', 'data' => [0, 2, 0, 0, 0, 0, 0]],
            ['label' => 'Email read', 'data' => [0, 1, 1, 0, 0, 0, 0]],
            ['label' => 'Email clicked', 'data' => [0, 4, 1, 0, 0, 0, 0]],
        ];
        Assert::assertEquals($expectedDaysLabels, $daysData['labels']);
        foreach ($daysDatasets as $index => $dataset) {
            Assert::assertEquals($expectedDaysData[$index]['label'], $dataset['label']);
            Assert::assertEquals($expectedDaysData[$index]['data'], $dataset['data']);
        }

        $hoursDatasets = $hoursData['datasets'];
        Assert::assertIsArray($hoursDatasets);
        Assert::assertCount(3, $hoursDatasets);  // Assuming there are 3 datasets: Email sent, Email read, Email clicked

        $expectedHoursLabels = [
            '00:00-01:00', '01:00-02:00', '02:00-03:00', '03:00-04:00', '04:00-05:00', '05:00-06:00', '06:00-07:00',
            '07:00-08:00', '08:00-09:00', '09:00-10:00', '10:00-11:00', '11:00-12:00', '12:00-13:00', '13:00-14:00',
            '14:00-15:00', '15:00-16:00', '16:00-17:00', '17:00-18:00', '18:00-19:00', '19:00-20:00', '20:00-21:00',
            '21:00-22:00', '22:00-23:00', '23:00-00:00',
        ];
        $expectedHoursData = [
            ['label' => 'Email sent', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]],
            ['label' => 'Email read', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0]],
            ['label' => 'Email clicked', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0]],
        ];
        Assert::assertEquals($expectedHoursLabels, $hoursData['labels']);
        foreach ($hoursDatasets as $index => $dataset) {
            Assert::assertEquals($expectedHoursData[$index]['label'], $dataset['label']);
            Assert::assertEquals($expectedHoursData[$index]['data'], $dataset['data']);
        }
    }
}
