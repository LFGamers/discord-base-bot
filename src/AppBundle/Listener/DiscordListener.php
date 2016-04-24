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
use Discord\Base\AppBundle\Factory\ServerManagerFactory;
use Discord\Base\AppBundle\Repository\BotCommandRepository;
use Discord\Base\AppBundle\Repository\IgnoredRepository;
use Discord\Base\Request;
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
use Discord\WebSockets\Events\GuildBanAdd;
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
     * @param EventDispatcherInterface $dispatcher
     * @param BotCommandRepository     $repository
     * @param RequestFactory           $factory
     * @param ServerManagerFactory     $managerFactory
     * @param IgnoredRepository        $ignoredRepository
     * @param                          $adminId
     */
    public function __construct(
        Discord $discord,
        EventDispatcherInterface $dispatcher,
        BotCommandRepository $repository,
        RequestFactory $factory,
        ServerManagerFactory $managerFactory,
        IgnoredRepository $ignoredRepository,
        $adminId
    ) {
        $this->discord           = $discord;
        $this->dispatcher        = $dispatcher;
        $this->commandRepository = $repository;
        $this->factory           = $factory;
        $this->managerFactory    = $managerFactory;
        $this->ignoredRepository = $ignoredRepository;
        $this->adminId           = $adminId;
    }

    /**
     *
     */
    public function listen()
    {
        $ws = $this->discord->ws;

        $ws->on(
            Event::GUILD_CREATE,
            function (Guild $guild) {
                $this->managerFactory->create($guild);
            }
        );

        $ws->on(
            Event::MESSAGE_CREATE,
            function (Message $message) {
                if ($message->author->id === $this->discord->client->id) {
                    return;
                }

                $request = $this->factory->create($message);
                if ($this->isIgnored($request)) {
                    return;
                }

                if (null === $message->full_channel->guild) {
                    $this->onPrivateMessage($request);

                    return;
                }

                $this->emitServerEvent($message->full_channel->guild, 'message', $request);
            }
        );

        $ws->on(
            Event::CHANNEL_CREATE,
            function (Channel $channel) {
                $this->emitServerEvent($channel->guild, 'channelCreate', $channel);
            }
        );

        $ws->on(
            Event::CHANNEL_UPDATE,
            function (Channel $channel) {
                $this->emitServerEvent($channel->guild, 'channelUpdate', $channel);
            }
        );

        $ws->on(
            Event::CHANNEL_DELETE,
            function (Channel $channel) {
                $this->emitServerEvent($channel->guild, 'channelDelete', $channel);
            }
        );

        $ws->on(
            Event::GUILD_BAN_ADD,
            function (Ban $ban) {
                $this->emitServerEvent($ban->guild, 'ban', $ban);
            }
        );

        $ws->on(
            Event::GUILD_BAN_REMOVE,
            function (Ban $ban) {
                $this->emitServerEvent($ban->guild, 'unban', $ban);
            }
        );

        $ws->on(
            Event::GUILD_DELETE,
            function (Guild $guild) {
                $this->emitServerEvent($guild, 'serverDelete', $guild);
            }
        );

        $ws->on(
            Event::GUILD_MEMBER_ADD,
            function (Member $member) {
                $guild = $this->discord->client->guilds->get('id', $member->guild_id);
                $this->emitServerEvent($guild, 'memberCreate', $member);
            }
        );

        $ws->on(
            Event::GUILD_MEMBER_REMOVE,
            function (Member $member) {
                $guild = $this->discord->client->guilds->get('id', $member->guild_id);
                $this->emitServerEvent($guild, 'memberDelete', $member);
            }
        );

        $ws->on(
            Event::GUILD_MEMBER_UPDATE,
            function (Member $member) {
                $guild = $this->discord->client->guilds->get('id', $member->guild_id);
                $this->emitServerEvent($guild, 'memberUpdate', $member);
            }
        );

        $ws->on(
            Event::GUILD_ROLE_CREATE,
            function (Role $role) {
                $guild = $this->discord->client->guilds->get('id', $role->guild_id);
                $this->emitServerEvent($guild, 'roleCreate', $role);
            }
        );

        $ws->on(
            Event::GUILD_ROLE_DELETE,
            function (Role $role) {
                $guild = $this->discord->client->guilds->get('id', $role->guild_id);
                $this->emitServerEvent($guild, 'roleDelete', $role);
            }
        );

        $ws->on(
            Event::GUILD_ROLE_UPDATE,
            function (Role $role) {
                $guild = $this->discord->client->guilds->get('id', $role->guild_id);
                $this->emitServerEvent($guild, 'roleUpdate', $role);
            }
        );

        $ws->on(
            Event::GUILD_UPDATE,
            function (Guild $guild) {
                $this->emitServerEvent($guild, 'serverUpdate', $guild);
            }
        );

        $ws->on(
            Event::MESSAGE_DELETE,
            function (Message $message) {
                $this->emitServerEvent($message->full_channel->guild, 'messageDelete', $message);
            }
        );

        $ws->on(
            Event::MESSAGE_UPDATE,
            function (Message $message) {
                $this->emitServerEvent($message->full_channel->guild, 'messageUpdate', $message);
            }
        );

        $ws->on(
            Event::PRESENCE_UPDATE,
            function (PresenceUpdate $presenceUpdate) {
                $this->emitServerEvent($presenceUpdate->guild, 'presenceUpdate', $presenceUpdate);
            }
        );

        $ws->on(
            Event::TYPING_START,
            function (TypingStart $typingStart) {
                $this->emitServerEvent($typingStart->channel->guild, 'typingStart', $typingStart);
            }
        );

        $ws->on(
            Event::VOICE_STATE_UPDATE,
            function (VoiceStateUpdate $voiceStateUpdate) {
                $this->emitServerEvent($voiceStateUpdate->guild, 'voiceStateUpdate', $voiceStateUpdate);
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

    private function isIgnored(Request $request)
    {
        $channelId = (string) $request->getChannel(true)->id;
        $serverId  = (string) $request->getServer()->id;
        $userId    = (string) $request->getAuthor()->id;

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
