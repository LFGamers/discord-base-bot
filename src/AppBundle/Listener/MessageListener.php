<?php

/**
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Listener;

use Discord\Base\AppBundle\Discord;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class MessageListener
{
    /**
     * @var Discord
     */
    private $discord;

    /**
     * MessageListener constructor.
     *
     * @param Discord $discord
     */
    public function __construct(Discord $discord)
    {
        $this->discord = $discord;
    }

    public function listen()
    {
        $this->discord->ws->on('message', function($message) {
            $this->onMessage($message);
        });
    }

    private function onMessage($message)
    {
    }
}
