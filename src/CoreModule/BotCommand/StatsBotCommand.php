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
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */
namespace Discord\Base\CoreModule\BotCommand;

use Discord\Base\AbstractBotCommand;
use Discord\Base\Request;
use Discord\Parts\User\Member;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class StatsBotCommand extends AbstractBotCommand
{
    public function configure()
    {
        $this->setName('stats')
            ->setDescription('Returns the statistics for the bot.');
    }

    /**
     * @return void
     */
    public function setHandlers()
    {
        $this->responds('/^stats$/i', [$this, 'renderStats']);
    }

    /**
     * @param Request $request
     */
    protected function renderStats(Request $request)
    {
        $users = $this->getUsers();
        $data  = [
            'servers'  => count($request->getDiscord()->client->guilds),
            'channels' => $this->getChannelCount(),
            'users'    => $users->count(),
            'online'   => count(
                $users->filter(
                    function (Member $user) {
                        return $user->status !== 'offline';
                    }
                )
            ),
            'channel'  => $request->isPrivateMessage() ? [] : [
                'channels' => count($request->getServer()->channels),
                'users'    => count($request->getServer()->members),
                'online'   => count(
                    $request->getServer()->getMembersAttribute()->filter(
                        function (Member $user) {
                            return $user->status !== 'offline';
                        }
                    )
                ),
            ],
        ];

        $request->reply($request->renderTemplate('@Core/stats.twig', $data));
    }

    /**
     * @return int
     */
    private function getChannelCount()
    {
        $channels = 0;
        foreach ($this->discord->client->guilds as $guild) {
            $channels += count($guild->channels);
        }

        return $channels;
    }

    /**
     * @return ArrayCollection|Member[]
     */
    private function getUsers()
    {
        $users = new ArrayCollection();
        foreach ($this->discord->client->guilds as $guild) {
            foreach ($guild->members as $user) {
                $users->add($user);
            }
        }

        return $users;
    }
}
