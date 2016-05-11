<?php

/*
 * This file is part of discord-base-bot
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
use Symfony\Component\VarDumper\VarDumper;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Discord
{
    /**
     * @var \Discord\Disc1ord
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
     * @param null   $shardId
     * @param null   $shardCount
     */
    public function __construct($token, $shardId = null, $shardCount = null)
    {
        $options = ['token' => $token];
        if (!is_null($shardId) && !is_null($shardCount)) {
            $options['shardId']    = $shardId;
            $options['shardCount'] = $shardCount;
        }

        $this->client = new \Discord\Discord($options);
        $this->ws     = new WebSocket($this->client);

        Cache::setCache(new ArrayCacheDriver());
    }
}
