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

use Discord\Base\AppBundle\Event\ServerEvent;
use Discord\Base\AppBundle\Factory\RequestFactory;
use Discord\Base\AppBundle\Factory\ServerManagerFactory;
use Discord\Base\AppBundle\Repository\BotCommandRepository;
use Discord\Base\AppBundle\Repository\IgnoredRepository;
use Discord\Base\Request;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Ban;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\Parts\WebSockets\TypingStart;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event;
use Monolog\Logger;
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
     * @var Logger
     */
    private $logger;

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
     * @var ServerManagerFactory
     */
    private $managerFactory;

    /**
     * @var IgnoredRepository
     */
    private $ignoredRepository;

    /**
     * @var string
     */
    private $adminId;

    /**
     * MessageListener constructor.
     *
     * @param Discord                  $discord
     * @param Logger                   $logger
     * @param EventDispatcherInterface $dispatcher
     * @param BotCommandRepository     $repository
     * @param RequestFactory           $factory
     * @param ServerManagerFactory     $managerFactory
     * @param IgnoredRepository        $ignoredRepository
     * @param int                      $adminId
     */
    public function __construct(
        Discord $discord,
        Logger $logger,
        EventDispatcherInterface $dispatcher,
        BotCommandRepository $repository,
        RequestFactory $factory,
        ServerManagerFactory $managerFactory,
        IgnoredRepository $ignoredRepository,
        $adminId
    ) {
        $this->discord           = $discord;
        $this->logger            = $logger;
        $this->dispatcher        = $dispatcher;
        $this->commandRepository = $repository;
        $this->factory           = $factory;
        $this->managerFactory    = $managerFactory;
        $this->ignoredRepository = $ignoredRepository;
        $this->adminId           = $adminId;
    }

    public function onMessageCreate(Message $message)
    {
        if ($message->author->id === $this->discord->id) {
            return;
        }

        $request = $this->factory->create($message);
        if ($this->isIgnored($request)) {
            return;
        }

        if (null === $request->getServer()) {
            $this->onPrivateMessage($request);

            return;
        }
        
        return $request->getServer()->members->fetch($request->getAuthor()->id)
            ->then(function(Member $member) use ($request) {
                $request->setGuildAuthor($member);
                $this->emitServerEvent($request->getServer(), 'message', $request);
            })
            ->otherwise(
                function ($e) use ($request) {
                    $this->emitServerEvent($request->getServer(), 'message', $request);
                }
            );
    }

    /**
     *
     */
    public function listen()
    {
        $this->discord->on(
            Event::GUILD_CREATE,
            function (Guild $guild) {
                $this->managerFactory->create($guild);
            }
        );

        $this->discord->on(
            Event::MESSAGE_CREATE,
            [$this, 'onMessageCreate']
        );

        $this->discord->on(
            Event::CHANNEL_CREATE,
            function (Channel $channel) {
                $this->emitServerEvent($channel->guild, 'channelCreate', $channel);
            }
        );

        $this->discord->on(
            Event::CHANNEL_UPDATE,
            function (Channel $channel) {
                $this->emitServerEvent($channel->guild, 'channelUpdate', $channel);
            }
        );

        $this->discord->on(
            Event::CHANNEL_DELETE,
            function (Channel $channel) {
                $this->emitServerEvent($channel->guild, 'channelDelete', $channel);
            }
        );

        $this->discord->on(
            Event::GUILD_BAN_ADD,
            function (Ban $ban) {
                $this->emitServerEvent($ban->guild, 'ban', $ban);
            }
        );

        $this->discord->on(
            Event::GUILD_BAN_REMOVE,
            function (Ban $ban) {
                $this->emitServerEvent($ban->guild, 'unban', $ban);
            }
        );

        $this->discord->on(
            Event::GUILD_DELETE,
            function (Guild $guild) {
                $this->emitServerEvent($guild, 'serverDelete', $guild);
            }
        );

        $this->discord->on(
            Event::GUILD_MEMBER_ADD,
            function (Member $member) {
                $guild = $this->discord->guilds->get('id', $member->guild_id);
                $this->emitServerEvent($guild, 'memberCreate', $member);
            }
        );

        $this->discord->on(
            Event::GUILD_MEMBER_REMOVE,
            function (Member $member) {
                $guild = $this->discord->guilds->get('id', $member->guild_id);
                $this->emitServerEvent($guild, 'memberDelete', $member);
            }
        );

        $this->discord->on(
            Event::GUILD_MEMBER_UPDATE,
            function (Member $member, $discord) {
                $guild = $this->discord->guilds->get('id', $member->guild_id);
                $this->emitServerEvent($guild, 'memberUpdate', $member, $discord);
            }
        );

        $this->discord->on(
            Event::GUILD_ROLE_CREATE,
            function (Role $role) {
                $guild = $this->discord->guilds->get('id', $role->guild_id);
                $this->emitServerEvent($guild, 'roleCreate', $role);
            }
        );

        $this->discord->on(
            Event::GUILD_ROLE_DELETE,
            function (Role $role) {
                $guild = $this->discord->guilds->get('id', $role->guild_id);
                $this->emitServerEvent($guild, 'roleDelete', $role);
            }
        );

        $this->discord->on(
            Event::GUILD_ROLE_UPDATE,
            function (Role $role) {
                $guild = $this->discord->guilds->get('id', $role->guild_id);
                $this->emitServerEvent($guild, 'roleUpdate', $role);
            }
        );

        $this->discord->on(
            Event::GUILD_UPDATE,
            function (Guild $guild) {
                $this->emitServerEvent($guild, 'serverUpdate', $guild);
            }
        );

        $this->discord->on(
            Event::MESSAGE_DELETE,
            function ($message) {
                if ($message instanceof Message) {
                    $this->emitServerEvent($message->channel->guild, 'messageDelete', $message);
                }
            }
        );

        $this->discord->on(
            Event::MESSAGE_UPDATE,
            function (Message $message) {
                $this->emitServerEvent($message->channel->guild, 'messageUpdate', $message);
            }
        );

        $this->discord->on(
            Event::PRESENCE_UPDATE,
            function (PresenceUpdate $presenceUpdate) {
                $this->emitServerEvent($presenceUpdate->guild, 'presenceUpdate', $presenceUpdate);
            }
        );

        $this->discord->on(
            Event::TYPING_START,
            function (TypingStart $typingStart) {
                $this->emitServerEvent($typingStart->channel->guild, 'typingStart', $typingStart);
            }
        );

        $this->discord->on(
            Event::VOICE_STATE_UPDATE,
            function (VoiceStateUpdate $voiceStateUpdate) {
                $this->emitServerEvent($voiceStateUpdate->guild, 'voiceStateUpdate', $voiceStateUpdate);
            }
        );
    }

    private function emitServerEvent(Guild $server = null, $type, ...$data)
    {
        if (empty($server)) {
            return false;
        }

        /** @var Guild $guild */
        foreach ($this->discord->guilds as $guild) {
            if ($guild->id === $server->id) {
                $params = [$guild, $type];
                $params = array_merge($params, $data);

                $event = call_user_func_array([ServerEvent::class, 'create'], $params);
                $this->dispatcher->dispatch(ServerEvent::class, $event);

                break;
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

    private function isIgnored(Request $request)
    {
        $channel = $request->getChannel();
        if (!is_object($channel)) {
            return false;
        }

        $server = $request->getServer();
        if (!is_object($server)) {
            return false;
        }

        $author = $request->getAuthor();
        if (!is_object($author)) {
            return false;
        }

        $channelId = (string) $channel->id;
        $serverId  = (string) $server->id;
        $userId    = (string) $author->id;

        if ($userId === $this->adminId) {
            return false;
        }

        foreach ($this->ignoredRepository->all() as $ignored) {
            if (!$ignored->getIgnored()) {
                continue;
            }
            if ($ignored->isType('channel') && $ignored->getIdentifier() === $channelId) {
                return true;
            }
            if ($ignored->isType('server') && $ignored->getIdentifier() === $serverId) {
                return true;
            }
            if ($ignored->isType('user') && $ignored->getIdentifier() === $userId) {
                return true;
            }
        }

        return false;
    }
}
