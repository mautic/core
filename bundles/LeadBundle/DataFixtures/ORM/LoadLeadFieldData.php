<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Class LoadLeadFieldData
 *
 * @package Mautic\LeadBundle\DataFixtures\ORM
 */
class LoadLeadFieldData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $translator = $this->container->get('translator');

        $textfields = array(
            'firstname',
            'lastname',
            'company',
            'position',
            'gender',
            'email',
            'title',
            'phone',
            'mobile',
            'fax',
            'address1',
            'address2',
            'city',
            'state',
            'zipcode',
            'country',
            'website',
            'twitter',
            'facebook',
            'googleplus',
            'skype',
            'instagram'
        );

        $leadsSchema = $this->container->get('mautic.factory')->getSchemaHelper('column', 'leads');

        foreach ($textfields as $key => $name) {
            $entity = new LeadField();
            $entity->setLabel($translator->trans('mautic.lead.field.'.$name, array(), 'fixtures'));
            if (in_array($name, array('title', 'company', 'city', 'state', 'zipcode', 'country'))) {
                $type = 'lookup';
            } elseif (in_array($name, array('phone', 'mobile', 'fax'))) {
                $type = 'tel';
            } elseif ($name == 'website') {
                $type = 'url';
            } elseif ($name == 'email') {
                $type = 'email';
            } else {
                $type = 'text';
            }

            if ($name == 'title') {
                $entity->setProperties(array("list" =>"|Mr|Mrs|Miss"));
            }
            $entity->setType($type);
            $fixed = in_array($name, array('firstname', 'lastname', 'position', 'company', 'email', 'address1', 'address2',
                'country', 'city', 'state')) ? true : false;
            $entity->setIsFixed($fixed);
            $entity->setOrder(($key+1));
            $entity->setAlias($name);
            $listable    = in_array($name, array(
                'address1',
                'address2',
                'fax',
                'phone',
                'twitter',
                'facebook',
                'googleplus',
                'skype',
                'mobile',
                'website'
            )) ? false : true;
            $entity->setIsListable($listable);

            $group = (in_array($name, array('twitter', 'facebook', 'googleplus', 'skype'))) ? 'social' : 'core';
            $entity->setGroup($group);

            $manager->persist($entity);
            $manager->flush();

            //add the column to the leads table
            $leadsSchema->addColumn(array('name' => $name));

            $this->addReference('leadfield-'.$name, $entity);
        }
        $leadsSchema->executeChanges();
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 4;
    }
}