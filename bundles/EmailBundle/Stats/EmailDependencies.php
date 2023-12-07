<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Stats;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\FormBundle\Model\ActionModel;
use Mautic\LeadBundle\Model\ListModel;

class EmailDependencies
{
    public function __construct(
        private CampaignModel $campaignModel,
        private ListModel $listModel,
        private ActionModel $actionModel
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChannelsIds(int $emailId): array
    {
        return [
            [
                'label' => 'mautic.campaign.campaigns',
                'route' => 'mautic_campaign_index',
                'ids'   => $this->campaignModel->getCampaignIdsWithDependenciesOnEmail($emailId),
            ],
            [
                'label' => 'mautic.lead.lead.lists',
                'route' => 'mautic_segment_index',
                'ids'   => $this->listModel->getSegmentIdsWithDependenciesOnEmail($emailId),
            ],
            [
                'label' => 'mautic.form.forms',
                'route' => 'mautic_form_index',
                'ids'   => $this->actionModel->getFormsIdsWithDependenciesOnEmail($emailId),
            ],
        ];
    }
}
