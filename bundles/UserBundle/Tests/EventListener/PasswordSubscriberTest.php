<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\EventListener\PasswordSubscriber;
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Router;
use ZxcvbnPhp\Zxcvbn;

class PasswordSubscriberTest extends TestCase
{
    /**
     * @var PasswordSubscriber
     */
    private $passwordSubscriber;

    /**
     * @var PasswordStrengthEstimatorModel
     */
    private $passwordStrengthEstimatorModel;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var AuthenticationEvent
     */
    private $authenticationEvent;

    /**
     * @var PluginToken
     */
    private $pluginToken;

    protected function setUp(): void
    {
        $this->passwordStrengthEstimatorModel = new PasswordStrengthEstimatorModel(new Zxcvbn());
        $this->userRepository                 = $this->createMock(UserRepository::class);
        $this->router                         = $this->createMock(Router::class);
        $this->passwordSubscriber             = new PasswordSubscriber($this->passwordStrengthEstimatorModel, $this->userRepository, $this->router);
        $this->authenticationEvent            = $this->createMock(AuthenticationEvent::class);
        $this->pluginToken                    = $this->createMock(PluginToken::class);

        $this->authenticationEvent->expects($this->any())
            ->method('getToken')
            ->willReturn($this->pluginToken);
    }

    public function testThatItThrowsExceptionIfPasswordIsWeak(): void
    {
        $this->expectException(WeakPasswordException::class);

        $simplePassword = '11111111';
        $this->pluginToken->expects($this->once())
            ->method('getCredentials')
            ->willReturn($simplePassword);

        $this->passwordSubscriber->onUserFormAuthentication($this->authenticationEvent);
    }
}
