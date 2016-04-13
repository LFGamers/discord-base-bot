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
    public function handle()
    {
        $this->responds('/^restart ?(\d+)?$/i', [$this, 'restart']);
    }

    /**
     * @param array $matches
     *
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    protected function restart(array $matches = [])
    {
        $time = isset($matches[1]) ? $matches[1] : 0;
        if ($time > 0) {
            $this->logger->info("Restarting in ${time} seconds.");

            return $this->runAfter($time, function () {
                $this->restart();
            });
        }

        $this->logger->info('Restarting!');
        die(1);
    }
}
