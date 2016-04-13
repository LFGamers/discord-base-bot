<?php

/*
 * This file is part of discord-base-bot.
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
     * @return ServerManager
     */
    public function create(Guild $guild)
    {
        return new ServerManager($this->container, $guild);
    }
}
