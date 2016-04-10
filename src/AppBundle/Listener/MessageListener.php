<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Listener;

use Discord\Base\AbstractBotCommand;
use Discord\Base\AppBundle\Discord;
use Discord\Base\AppBundle\Repository\BotCommandRepository;
use Monolog\Logger;

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
     * @var Logger
     */
    private $logger;

    /**
     * @var BotCommandRepository
     */
    private $commandRepository;

    /**
     * MessageListener constructor.
     *
     * @param Discord              $discord
     * @param Logger               $logger
     * @param BotCommandRepository $commandRepository
     */
    public function __construct(Discord $discord, Logger $logger, BotCommandRepository $commandRepository)
    {
        $this->discord           = $discord;
        $this->logger            = $logger;
        $this->commandRepository = $commandRepository;
    }

    /**
     *
     */
    public function listen()
    {
        $this->discord->ws->on('message', [$this, 'onMessage']);
    }

    /**
     * @param $message
     */
    public function onMessage($message)
    {
        if ($message->author->id === $this->discord->client->id) {
            return;
        }

        foreach ($this->commandRepository->all() as $command) {
            $command->setMessage($message);

            $command->handle();
        }
    }
}
