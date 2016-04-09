<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

/**
 * This file is part of discord-server-list-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */
namespace Discord\Base\AppBundle;

use Discord\Cache\Cache;
use Discord\Cache\Drivers\ArrayCacheDriver;
use Discord\WebSockets\WebSocket;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Discord
{
    /**
     * @var \Discord\Discord
     */
    public $client;

    /**
     * @var WebSocket
     */
    public $ws;

    /**
     * Discord constructor.
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->client = new \Discord\Discord(['token' => $token]);
        $this->ws     = new WebSocket($this->client);

        Cache::setCache(new ArrayCacheDriver());
    }
}
