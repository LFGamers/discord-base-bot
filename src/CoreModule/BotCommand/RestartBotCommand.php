<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\CoreModule\BotCommand;

use Discord\Base\AbstractBotCommand;
use Discord\Base\Request;
use React\EventLoop\Timer\Timer;
use React\EventLoop\Timer\TimerInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class RestartBotCommand extends AbstractBotCommand
{
    public function configure()
    {
        $this->setName('restart')
            ->setDescription('Restarts the bot')
            ->setAdminCommand(true);
    }

    /**
     * @return void
     */
    public function setHandlers()
    {
        $this->responds('/^restart ?(\d+)?$/i', [$this, 'restart']);
    }

    /**
     * @param Request $request
     * @param array   $matches
     *
     * @return Timer|TimerInterface
     */
    protected function restart(Request $request, array $matches = [])
    {
        $time = isset($matches[1]) ? $matches[1] : 0;
        if ($time > 0) {
            $this->logger->info("Restarting in ${time} seconds.");

            return $request->runAfter($time, function () use ($request) {
                $this->restart($request);
            });
        }

        $request->getLogger()->info('Restarting!');
        $request->reply('Restarting');
        die(1);
    }
}
