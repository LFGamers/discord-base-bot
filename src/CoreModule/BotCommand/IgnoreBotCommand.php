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
 * This file is part of discord-base-bot.
 *
 * Copyright (c) 2016 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

/**
 * This file is part of discord-base-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */
namespace Discord\Base\CoreModule\BotCommand;

use Discord\Base\AbstractBotCommand;
use Discord\Base\AppBundle\Model\Ignored;
use Discord\Base\AppBundle\Model\IgnoredChannel;
use Discord\Base\AppBundle\Model\IgnoredServer;
use Discord\Base\AppBundle\Model\IgnoredUser;
use Discord\Base\Request;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class IgnoreBotCommand extends AbstractBotCommand
{
    /**
     *
     */
    public function configure()
    {
        $this->setName('ignore')
            ->setDescription('Configure ignores for the bot.')
            ->setAdminCommand(true)
            ->setHelp(
                <<<'EOF'
The ignore command lets you list, ignore, and unignore users, channels, and servers.

`ignore list` Lists all ignores for the bot

`ignore server` Ignores the current server
`unignore server` Unignores the current server

`ignore @user` Ignores the give user
`unignore @user` Unignores the give user

`ignore <type> <id>` Ignores the given type, with the given id
`unignore <type> <id>` Ignores the given type, with the given id
EOF
            );
    }

    /**
     * @return void
     */
    public function setHandlers()
    {
        $this->responds(
            '/^ignore$/i',
            function (Request $request) {
                $request->reply($this->getHelp());
            }
        );

        $this->responds('/^ignore list$/i', [$this, 'renderIgnoreList']);
        $this->responds('/^(un)?ignore server$/', [$this, 'toggleCurrentServer']);
        $this->responds('/^(un)?ignore <@(\d+)>$/', [$this, 'toggleUser']);
        $this->responds('/^(un)?ignore (user|channel|server) ([0-9]+)$/', [$this, 'toggleIgnore']);
    }

    /**
     * @param Request $request
     */
    protected function renderIgnoreList(Request $request)
    {
        $ignores = $this->getManager()->getRepository('App:Ignored')->findAll();
        foreach ($ignores as &$ignored) {
            if ($ignored instanceof IgnoredChannel) {
                $ignored->name = $this->getChannelName($ignored->getIdentifier());
            }
            if ($ignored instanceof IgnoredServer) {
                $ignored->name = $this->getServerName($ignored->getIdentifier());
            }
            if ($ignored instanceof IgnoredUser) {
                $ignored->name = $this->getUserName($ignored->getIdentifier());
            }
        }

        $request->reply($request->renderTemplate('@Core/ignore/list.twig', ['ignores' => $ignores]));
    }

    /**
     * @param Request $request
     * @param array   $matches
     */
    protected function toggleCurrentServer(Request $request, array $matches)
    {
        $ignore = $this->setIgnored(empty($matches[1]), 'server', $request->getServer()->id);

        $request->reply(($ignore ? 'Ignored' : 'Unignored').' '.$request->getServer()->name);
    }

    /**
     * @param Request $request
     * @param array   $matches
     */
    protected function toggleUser(Request $request, array $matches)
    {
        $ignore = $this->setIgnored(empty($matches[1]), 'user', $matches[2]);

        $request->reply(
            ($ignore ? 'Ignored' : 'Unignored').' '.
            $request->getMentions()[0]->username.'#'.$request->getMentions()[0]->discriminator
        );
    }

    /**
     * @param Request $request
     * @param array   $matches
     */
    protected function toggleIgnore(Request $request, array $matches)
    {
        $ignore = $this->setIgnored(empty($matches[1]), $matches[2], $matches[3]);

        $request->reply($ignore ? 'Ignored' : 'Unignored');
    }

    /**
     * @param bool   $ignore
     * @param string $type
     * @param string $identifier
     *
     * @return bool
     */
    private function setIgnored($ignore, $type, $identifier)
    {
        $repo    = $this->getManager()->getRepository('App:Ignored'.ucfirst($type));
        $ignored = $repo->findOneBy(['identifier' => $identifier]);
        if (empty($ignored)) {
            $ignored = $type === 'user'
                ? new IgnoredUser()
                : ($type === 'server' ? new IgnoredServer() : new IgnoredChannel());

            $ignored->setIdentifier($identifier);
            $this->getManager()->persist($ignored);
        }

        $ignored->setIgnored($ignore);
        $this->getManager()->flush();

        return $ignore;
    }

    /**
     * @param string $channelId
     *
     * @return string
     */
    private function getChannelName($channelId)
    {
        foreach ($this->discord->client->guilds as $guild) {
            foreach ($guild->channels as $channel) {
                if ($channel->id === $channelId) {
                    return $channel->name;
                }
            }
        }

        return 'No name';
    }

    /**
     * @param string $serverId
     *
     * @return string
     */
    private function getServerName($serverId)
    {
        foreach ($this->discord->client->guilds as $guild) {
            if ($guild->id === $serverId) {
                return $guild->name;
            }
        }

        return 'No name';
    }

    /**
     * @param string $userId
     *
     * @return string
     */
    private function getUserName($userId)
    {
        foreach ($this->discord->client->guilds as $guild) {
            foreach ($guild->members as $member) {
                if ((string) $member->id === $userId) {
                    return $member->username.'#'.$member->discriminator;
                }
            }
        }

        return 'No name';
    }
}
