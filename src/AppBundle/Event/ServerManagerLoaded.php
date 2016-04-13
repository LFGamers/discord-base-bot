<?php

/*
 * This file is part of discord-base-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Event;

use Discord\Base\AppBundle\Manager\ServerManager;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * ServerManagerLoaded Class
 */
class ServerManagerLoaded extends Event
{
    /**
     * @var ServerManager
     */
    private $manager;

    /**
     * @param ServerManager $manager
     *
     * @return ServerManagerLoaded
     */
    public static function create(ServerManager $manager)
    {
        return new self($manager);
    }

    /**
     * ServerManagerLoaded constructor.
     *
     * @param ServerManager $manager
     */
    public function __construct(ServerManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return ServerManager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
