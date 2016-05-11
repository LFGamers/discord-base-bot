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
use Discord\Base\AppBundle\Model\Server;
use Discord\Base\AppBundle\Repository\ServerManagerRepository;
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
     * @param Guild       $guild
     * @param Server|null $server
     *
     * @return ServerManager
     * @throws \Exception
     */
    public function create(Guild $guild, Server $server = null)
    {
        /** @var ServerManagerRepository $repo */
        $repo = $this->container->get('repository.server_manager');

        $cls = $this->container->getParameter('server_manager_class');

        if ($repo->has($guild->id)) {
            return $repo->get($guild->id);
        }
        
        $instance = new $cls($this->container, $guild, $server);

        if (!($instance instanceof ServerManager)) {
            throw new \Exception('ServerManager must extend '.ServerManager::class);
        }

        $repo->push($instance);

        return $instance;
    }
}
