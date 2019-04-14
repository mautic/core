<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Install;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Configurator\Configurator;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\InstallBundle\Helper\SchemaHelper;
use Mautic\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class InstallService.
 */
class InstallService
{
    /**
     * @var Configurator
     */
    private $configurator;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var CacheHelper
     */
    private $cacheHelper;

    /**
     * @var PathsHelper
     */
    protected $pathsHelper;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var KernelInterface
     */
    private $kernel;
    /**
     * @var EncoderFactory
     */
    private $encoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * InstallService constructor.
     *
     * @param Configurator         $configurator
     * @param CoreParametersHelper $coreParametersHelper
     * @param CacheHelper          $cacheHelper
     * @param PathsHelper          $pathsHelper
     * @param EntityManager        $entityManager
     * @param TranslatorInterface  $translator
     * @param KernelInterface      $kernel
     * @param EncoderFactory       $encoder
     * @param LoggerInterface      $logger
     */
    public function __construct(Configurator $configurator,
                                CoreParametersHelper $coreParametersHelper,
                                CacheHelper $cacheHelper,
                                PathsHelper $pathsHelper,
                                EntityManager $entityManager,
                                TranslatorInterface $translator,
                                KernelInterface $kernel,
                                EncoderFactory $encoder,
                                LoggerInterface $logger)
    {
        $this->configurator             = $configurator;
        $this->coreParametersHelper     = $coreParametersHelper;
        $this->cacheHelper              = $cacheHelper;
        $this->pathsHelper              = $pathsHelper;
        $this->entityManager            = $entityManager;
        $this->translator               = $translator;
        $this->kernel                   = $kernel;
        $this->encoder                  = $encoder;
        $this->logger                   = $logger;
    }

    /**
     * Get step object for given index or appropriate step index.
     *
     * @param int $index The step number to retrieve
     *
     * @return int|StepInterface the valid
     *
     * @throws \InvalidArgumentException
     */
    public function getStep($index = 0)
    {
        // We're going to assume a bit here; if the config file exists already and DB info is provided, assume the app
        // is installed and redirect
        if ($this->checkIfInstalled()) {
            return true;
        }

        if (($pos = strpos($index, '.')) !== false) {
            $index = (int) substr($index, 0, $pos);
        }

        $params = $this->configurator->getParameters();

        // Check to ensure the installer is in the right place
        if ((empty($params) || empty($params['db_driver'])) && $index > 1) {
            return 1;
        }

        return $this->configurator->getStep($index)[0];
    }

    /**
     * Get local config file location.
     *
     * @return string
     */
    private function localConfig()
    {
        return $this->pathsHelper->getSystemPath('local_config');
    }

    /**
     * Get local config parameters.
     *
     * @return array
     */
    public function localConfigParameters()
    {
        $localConfigFile = $this->localConfig();

        if (file_exists($localConfigFile)) {
            /** @var array $parameters */
            include $localConfigFile;
            $localParameters = (isset($parameters) && is_array($parameters)) ? $parameters : [];
        } else {
            $localParameters = [];
        }

        return $localParameters;
    }

    /**
     * Checks if the application has been installed and redirects if so.
     *
     * @return bool
     */
    public function checkIfInstalled()
    {
        // If the config file doesn't even exist, no point in checking further
        $localConfigFile = $this->localConfig();
        if (!file_exists($localConfigFile)) {
            return false;
        }

        /** @var \Mautic\CoreBundle\Configurator\Configurator $configurator */
        $params = $this->configurator->getParameters();

        // if db_driver and mailer_from_name are present then it is assumed all the steps of the installation have been
        // performed; manually deleting these values or deleting the config file will be required to re-enter
        // installation.
        if (empty($params['db_driver']) || empty($params['mailer_from_name'])) {
            return false;
        }

        return true;
    }

    /**
     * Translation messages array.
     *
     * @param array $messages
     *
     * @return array
     */
    private function translateMessage($messages)
    {
        $translator = $this->translator;

        if (is_array($messages) && !empty($messages)) {
            foreach ($messages as $key => $value) {
                $messages[$key] = $translator->trans($value);
            }
        }

        return $messages;
    }

    /**
     * Checks for step's requirements.
     *
     * @param null|StepInterface $step
     *
     * @return array
     */
    public function checkRequirements($step)
    {
        $messages = $step->checkRequirements();

        return $this->translateMessage($messages);
    }

    /**
     * Checks for step's optional settings.
     *
     * @param null|StepInterface $step
     *
     * @return array
     */
    public function checkOptionalSettings($step)
    {
        $messages = $step->checkOptionalSettings();

        return $this->translateMessage($messages);
    }

    /**
     * @param array|StepInterface $params
     * @param null|StepInterface  $step
     * @param bool                $clearCache
     *
     * @return bool
     */
    public function saveConfiguration($params, $step = null, $clearCache = false)
    {
        $translator = $this->translator;

        if (null !== $step) {
            $params = $step->update($params);
        }

        $this->configurator->mergeParameters($params);

        $messages = false;

        try {
            $this->configurator->write();
            $messages = true;
        } catch (\RuntimeException $exception) {
            $messages = [];
            $messages['error'] = $translator->trans(
                'mautic.installer.error.writing.configuration',
                [],
                'flashes');
        }

        if ($clearCache) {
            $this->cacheHelper->clearContainerFile(false);
        }
        $this->cacheHelper->refreshConfig();

        return $messages;
    }

    /**
     * @param array $dbParams
     *
     * @return array|bool
     */
    public function validateDatabaseParams($dbParams)
    {
        $translator = $this->translator;

        $required = [
            'host',
            'name',
            'user',
        ];

        $messages = [];
        foreach ($required as $r) {
            if (empty($dbParams[$r])) {
                $messages[$r] = $translator->trans(
                    'mautic.core.value.required',
                    [],
                    'validators'
                );
            }
        }

        if ((int) $dbParams['port'] <= 0) {
            $messages['port'] = $translator->trans(
                'mautic.install.database.port.invalid',
                [],
                'validators'
            );
        }

        return empty($messages) ? true : $messages;
    }

    /**
     * Create the database.
     *
     * @param null|StepInterface $step
     * @param array              $dbParams
     *
     * @return array|bool
     */
    public function createDatabaseStep($step, $dbParams)
    {
        $translator = $this->translator;

        $messages = $this->validateDatabaseParams($dbParams);

        if (is_bool($messages) && $messages === true) {
            // Check if connection works and/or create database if applicable
            $schemaHelper = new SchemaHelper($dbParams);

            try {
                $schemaHelper->testConnection();

                if ($schemaHelper->createDatabase()) {
                    $messages = $this->saveConfiguration($dbParams, $step, true);
                    if (is_bool($messages)) {
                        return $messages;
                    }

                    $messages['error'] = $translator->trans(
                        'mautic.installer.error.writing.configuration',
                        [],
                        'flashes');
                } else {
                    $messages['error'] = $translator->trans(
                        'mautic.installer.error.creating.database',
                        ['%name%' => $dbParams['name']],
                        'flashes');
                }
            } catch (\Exception $exception) {
                $messages['error'] = $translator->trans(
                    'mautic.installer.error.connecting.database',
                    ['%exception%' => $exception->getMessage()],
                    'flashes');
            }
        }

        return $messages;
    }

    /**
     * Create the database schema.
     *
     * @param array $dbParams
     *
     * @return array|bool
     */
    public function createSchemaStep($dbParams)
    {
        $translator   = $this->translator;
        $schemaHelper = new SchemaHelper($dbParams);
        $schemaHelper->setEntityManager($this->entityManager);

        $messages = [];
        try {
            if (!$schemaHelper->installSchema()) {
                $messages['error'] = $translator->trans(
                    'mautic.installer.error.no.metadata',
                    [],
                    'flashes');
            } else {
                $messages = true;
            }
        } catch (\Exception $exception) {
            $messages['error'] = $translator->trans(
                'mautic.installer.error.installing.data',
                ['%exception%' => $exception->getMessage()],
                'flashes');
        }

        return $messages;
    }

    /**
     * Load the database fixtures in the database.
     *
     * @param ContainerInterface $container
     *
     * @return array|bool
     */
    public function createFixturesStep(ContainerInterface $container)
    {
        $translator = $this->translator;

        $messages = [];
        try {
            $this->installDatabaseFixtures($container);
            $messages = true;
        } catch (\Exception $exception) {
            $messages['error'] = $translator->trans(
                'mautic.installer.error.adding.fixtures',
                ['%exception%' => $exception->getMessage()],
                'flashes');
        }

        return $messages;
    }

    /**
     * Installs data fixtures for the application.
     *
     * @param ContainerInterface $container
     *
     * @return bool boolean true on success
     */
    public function installDatabaseFixtures(ContainerInterface $container)
    {
        $entityManager = $this->entityManager;
        $paths         = [dirname(__DIR__).'/InstallFixtures/ORM'];
        $loader        = new ContainerAwareLoader($container);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        $fixtures = $loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Could not find any fixtures to load in: %s',
                    "\n\n- ".implode("\n- ", $paths)
                )
            );
        }

        $purger = new ORMPurger($entityManager);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $executor = new ORMExecutor($entityManager, $purger);
        $executor->execute($fixtures, true);

        return true;
    }

    /**
     * Create the administrator user.
     *
     * @param array $data
     *
     * @return array|bool
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createAdminUserStep($data)
    {
        $entityManager = $this->entityManager;

        //ensure the username and email are unique
        try {
            $existingUser = $entityManager->getRepository('MauticUserBundle:User')->find(1);
        } catch (\Exception $e) {
            $existingUser = null;
        }

        if (null != $existingUser) {
            $user = $existingUser;
        } else {
            $user = new User();
        }

        $required = [
            'firstname',
            'lastname',
            'username',
            'email',
            'password',
        ];

        $translator = $this->translator;

        $messages = [];
        foreach ($required as $r) {
            if (empty($data[$r])) {
                $messages[$r] = $translator->trans(
                    'mautic.core.value.required',
                    [],
                    'validators'
                );
            }
        }

        if (!empty($messages)) {
            return $messages;
        }

        $encoder = $this->encoder->getEncoder($user);

        $user->setFirstName($data['firstname']);
        $user->setLastName($data['lastname']);
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($encoder->encodePassword($data['password'], $user->getSalt()));

        $adminRole = null;
        try {
            $adminRole = $entityManager->getReference('MauticUserBundle:Role', 1);
        } catch (\Exception $exception) {
            $messages['error'] = $translator->trans(
                'mautic.installer.error.getting.role',
                ['%exception%' => $exception->getMessage()],
                'flashes'
            );
        }

        if (!empty($adminRole)) {
            $user->setRole($adminRole);

            try {
                $entityManager->persist($user);
                $entityManager->flush();
            } catch (\Exception $exception) {
                $messages['error'] = $translator->trans(
                    'mautic.installer.error.creating.user',
                    ['%exception%' => $exception->getMessage()],
                    'flashes'
                );
            }
        }

        return $messages;
    }

    /**
     * Create the final configuration.
     *
     * @param string $siteUrl
     *
     * @return array|bool
     */
    public function createFinalConfigStep($siteUrl)
    {
        // Merge final things into the config, wipe the container, and we're done!
        $finalConfigVars = [
            'secret_key' => EncryptionHelper::generateKey(),
            'site_url'   => $siteUrl,
        ];

        $messages = $this->saveConfiguration($finalConfigVars, null, true);
        if (is_bool($messages)) {
            return $messages;
        }

        return $messages;
    }

    /**
     * Final migration step for install.
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function finalMigrationStep()
    {
        // Add database migrations up to this point since this is a fresh install (must be done at this point
        // after the cache has been rebuilt
        $input  = new ArgvInput(['console', 'doctrine:migrations:version', '--add', '--all', '--no-interaction']);
        $output = new BufferedOutput();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run($input, $output);

        return true;
    }
}
