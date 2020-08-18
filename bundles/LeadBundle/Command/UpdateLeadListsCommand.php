<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Segment\Query\QueryException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLeadListsCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:segments:update')
            ->setAliases(['mautic:segments:rebuild'])
            ->setDescription('Update contacts in smart segments based on new contact data.')
            ->addOption(
                '--batch-limit',
                '-b',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts to process per round. Defaults to 300.',
                300
            )
            ->addOption(
                '--max-contacts',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Set max number of contacts to process per segment for this script execution. Defaults to all.',
                false
            )
            ->addOption(
                '--list-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Specific ID to rebuild. Defaults to all.',
                false
            )
            ->addOption(
                '--timing',
                '-tm',
                InputOption::VALUE_OPTIONAL,
                'Measure timing of build with output to CLI .',
                false
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $translator = $container->get('translator');

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $container->get('mautic.lead.model.list');

        $id                    = $input->getOption('list-id');
        $batch                 = $input->getOption('batch-limit');
        $max                   = $input->getOption('max-contacts');
        $enableTimeMeasurement = (bool) $input->getOption('timing');
        $output                = ($input->getOption('quiet')) ? new NullOutput() : $output;

        if (!$this->checkRunStatus($input, $output, $id)) {
            return 0;
        }

        if ($enableTimeMeasurement) {
            $startTime = microtime(true);
        }

        if ($id) {
            $list = $listModel->getEntity($id);

            if (null !== $list) {
                if ($list->isPublished()) {
                    $output->writeln('<info>'.$translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $id]).'</info>');
                    $processed = 0;
                    try {
                        $processed = $listModel->rebuildListLeads($list, $batch, $max, $output);
                        if (0 >= $max) {
                            // Only full rebuild counts
                            $this->updateLastBuiltDate($list);
                            $listModel->saveEntity($list);
                        }
                    } catch (QueryException $e) {
                        $this->getContainer()->get('monolog.logger.mautic')->error('Query Builder Exception: '.$e->getMessage());
                    }

                    $output->writeln(
                        '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'
                    );
                }
            } else {
                $output->writeln('<error>'.$translator->trans('mautic.lead.list.rebuild.not_found', ['%id%' => $id]).'</error>');
            }
        } else {
            $lists = $listModel->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            while (false !== ($l = $lists->next())) {
                // Get first item; using reset as the key will be the ID and not 0
                $l = reset($l);

                if ($l->isPublished()) {
                    $output->writeln('<info>'.$translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $l->getId()]).'</info>');

                    $startTimeForSingleSegment = time();
                    $processed                 = $listModel->rebuildListLeads($l, $batch, $max, $output);
                    if (0 >= $max) {
                        // Only full rebuild counts
                        $this->updateLastBuiltDate($l);
                        $listModel->saveEntity($l);
                    }
                    $output->writeln(
                        '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'
                    );
                    if ($enableTimeMeasurement) {
                        $totalTime = round(microtime(true) - $startTimeForSingleSegment, 2);
                        $output->writeln($translator->trans('mautic.lead.list.rebuild.total.time', ['%time%' => $totalTime])."\n");
                    }
                }

                unset($l);
            }

            unset($lists);
        }

        $this->completeRun();

        if ($enableTimeMeasurement) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $output->writeln($translator->trans('mautic.lead.list.rebuild.total.time', ['%time%' => $totalTime]));
        }

        return 0;
    }

    private function updateLastBuiltDate(LeadList $leadList): void
    {
        $now = (new DateTimeHelper())->getUtcDateTime();
        $leadList->setLastBuiltDate($now);
    }
}
