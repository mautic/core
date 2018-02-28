<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\InactiveExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TriggerCampaignCommand.
 */
class ValidateEventCommand extends Command
{
    /**
     * @var InactiveExecutioner
     */
    private $inactiveExecution;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ExecuteEventCommand constructor.
     *
     * @param ScheduledExecutioner $scheduledExecutioner
     */
    public function __construct(InactiveExecutioner $inactiveExecutioner, TranslatorInterface $translator)
    {
        parent::__construct();

        $this->inactiveExecution = $inactiveExecutioner;
        $this->translator        = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:validate')
            ->setDescription('Validate if a contact has been inactive for a decision and execute events if so.')
            ->addOption(
                '--decision-id',
                null,
                InputOption::VALUE_REQUIRED,
                'ID of the decision to evaluate.'
            )
            ->addOption(
                '--contact-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Evaluate for specific contact'
            )
            ->addOption(
                '--contact-ids',
                null,
                InputOption::VALUE_OPTIONAL,
                'CSV of contact IDs to evaluate.'
            );

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $decisionId = $input->getOption('decision-id');
        $contactId  = $input->getOption('contact-id');
        if ($contactIds = $input->getOption('contact-ids')) {
            $contactIds = array_map(
                function ($id) {
                    return (int) trim($id);
                },
                explode(',', $contactIds)
            );
        }

        if (!$contactIds && !$contactId) {
            $output->writeln(
                "\n".
                '<comment>'.$this->translator->trans('mautic.campaign.trigger.events_executed', ['%events%' => 0])
                .'</comment>'
            );

            return 0;
        }

        $limiter = new ContactLimiter(null, $contactId, null, null, $contactIds);
        $counter = $this->inactiveExecution->validate($decisionId, $limiter, $output);

        $output->writeln(
            "\n".
            '<comment>'.$this->translator->trans('mautic.campaign.trigger.events_executed', ['%events%' => $counter->getExecuted()])
            .'</comment>'
        );
        $output->writeln('');

        return 0;
    }
}
