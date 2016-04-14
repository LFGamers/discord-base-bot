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

use Discord\Base\AppBundle\Discord;
use Discord\Base\AppBundle\Event\ServerEvent;
use Discord\Base\AppBundle\Factory\RequestFactory;
use Discord\Base\AppBundle\Repository\BotCommandRepository;
use Discord\Base\Request;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class DiscordListener
{
    /**
     * @var Discord
     */
    private $discord;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var BotCommandRepository
     */
    private $commandRepository;

    /**
     * @var RequestFactory
     */
    private $factory;

    /**
     * MessageListener constructor.
     *
     * @param Discord                  $discord
     * @param EventDispatcherInterface $dispatcher
     * @param BotCommandRepository     $repository
     * @param RequestFactory           $factory
     */
    public function __construct(
        Discord $discord,
        EventDispatcherInterface $dispatcher,
        BotCommandRepository $repository,
        RequestFactory $factory
    ) {
        $this->discord           = $discord;
        $this->dispatcher        = $dispatcher;
        $this->commandRepository = $repository;
        $this->factory           = $factory;
    }

    /**
     *
     */
    public function listen()
    {
        $ws = $this->discord->ws;

        $ws->on(
            Event::MESSAGE_CREATE,
            function (Message $message) {
                if ($message->author->id === $this->discord->client->id) {
                    return;
                }

                $request = $this->factory->create($message);
                if (null === $message->full_channel->guild) {
                    $this->onPrivateMessage($request);

                    return;
                }

                $this->emitServerEvent($message->full_channel->guild, 'message', ['request' => $request]);
            }
        );
    }

    private function emitServerEvent(Guild $server = null, $type, array $data)
    {
        if (empty($server)) {
            return false;
        }

        /** @var Guild $guild */
        foreach ($this->discord->client->guilds as $guild) {
            if ($guild->getAttribute('id') === $server->getAttribute('id')) {
                $this->dispatcher->dispatch(ServerEvent::class, ServerEvent::create($guild, $type, $data));
            }
        }
    }

    /**
     * @param Request $request
     */
    public function onPrivateMessage(Request $request)
    {
        foreach ($this->commandRepository->all() as $command) {
            $request->processCommand($command);

            if ($request->isHandled()) {
                return;
            }
        }
    }
}