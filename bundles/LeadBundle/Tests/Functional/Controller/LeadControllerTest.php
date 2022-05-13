<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\ContactScheduledExportCommand;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class LeadControllerTest extends MauticMysqlTestCase
{
    /** @var array<string> */
    private array $filePaths = [];

    protected function setUp(): void
    {
        $this->configParams['contact_export_dir'] = '/tmp';
        parent::setUp();
    }

    protected function beforeTearDown(): void
    {
        foreach ($this->filePaths as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * @dataProvider getContactExportIsScheduledProvider
     */
    public function testContactExportIsScheduled(string $fileType): void
    {
        $this->createContacts();
        $this->client->request(
            Request::METHOD_POST,
            's/contacts/contactExportScheduler',
            ['filetype' => $fileType]
        );
        Assert::assertTrue($this->client->getResponse()->isOk());
        $contactExportSchedulerRows = $this->checkContactExportScheduler(1);
        $contactExportScheduler     = $contactExportSchedulerRows[0];
        \assert($contactExportScheduler instanceof ContactExportScheduler);
        $contactExportSchedulerData = $contactExportScheduler->getData();
        $this->runCommand(ContactScheduledExportCommand::COMMAND_NAME);
        $this->checkContactExportScheduler(0);
        $coreParametersHelper = self::$container->get('mautic.helper.core_parameters');
        \assert($coreParametersHelper instanceof CoreParametersHelper);
        $fileType = $contactExportSchedulerData['fileType'];
        $fileName = 'contacts_export_'.$contactExportScheduler->getScheduledDateTime()
                ->format('Y_m_d_H_i_s').'.'.$fileType;
        $this->filePaths[] = $filePath = $coreParametersHelper->get('contact_export_dir').'/'.$fileName;
        Assert::assertFileExists($filePath);
    }

    /**
     * @return iterable<mixed>
     */
    public function getContactExportIsScheduledProvider(): iterable
    {
        yield ['csv'];
        yield ['xlsx'];
    }

    private function createContacts(): void
    {
        $contacts = [];

        for ($i = 1; $i <= 2; ++$i) {
            $contact = new Lead();
            $contact
                ->setFirstname('ContactFirst'.$i)
                ->setLastname('ContactLast'.$i)
                ->setEmail('FirstLast'.$i.'@email.com');
            $contacts[] = $contact;
        }

        $leadModel = self::$container->get('mautic.lead.model.lead');
        $leadModel->saveEntities($contacts);
    }

    /**
     * @return array<mixed>
     */
    private function checkContactExportScheduler(int $count): array
    {
        $repo    = $this->em->getRepository(ContactExportScheduler::class);
        $allRows = $repo->findAll();
        Assert::assertCount($count, $allRows);

        return $allRows;
    }
}
