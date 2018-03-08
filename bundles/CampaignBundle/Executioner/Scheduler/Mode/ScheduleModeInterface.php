<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Scheduler\Mode;

use Mautic\CampaignBundle\Entity\Event;

interface ScheduleModeInterface
{
    /**
     * @param Event     $event
     * @param \DateTime $now
     * @param \DateTime $comparedToDateTime
     *
     * @return mixed
     */
    public function getExecutionDateTime(Event $event, \DateTime $now, \DateTime $comparedToDateTime);
}
