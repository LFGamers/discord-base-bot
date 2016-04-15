<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Factory;

use Discord\Base\AppBundle\Manager\ServerManager;
use Discord\Parts\Guild\Guild;
use Symfony\Component\DependencyInjection\Container;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * ServerManagerFactory Class
 */
class ServerManagerFactory
{
    /**
     * @var Container
     */
    private $container;

    /**
     * ServerManagerFactory constructor.
     *
     * @param Container $container
     *
     * @internal param Discord $discord
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Guild $guild
     *
     * @throws \Exception
     *
     * @return ServerManager
     */
    public function create(Guild $guild)
    {
        $cls = $this->container->getParameter('server_manager_factory');

        $instance = new $cls($this->container, $guild);

        if (!($instance instanceof ServerManager)) {
            throw new \Exception('ServerManager must extend '.ServerManager::class);
        }

        return $instance;
    }
}
