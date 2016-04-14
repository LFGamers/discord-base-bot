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

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class EvalBotCommand extends AbstractBotCommand
{
    public function configure()
    {
        $this->setName('eval')
            ->setDescription('Runs the given code, with the bot.')
            ->setAdminCommand(true);
    }

    /**
     * @return void
     */
    public function setHandlers()
    {
        $this->responds('/^eval(?:\s+)```[a-z]*\n([\s\S]*)?\n```$/i', [$this, 'evalCode']);
        $this->responds('/^eval(?:\s+)`?([^`]*)?`?$/i', [$this, 'evalCode']);
    }

    /**
     * @param Request $request
     * @param array   $matches
     *
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    protected function evalCode(Request $request, array $matches = [])
    {
        $message  = $request->reply('Executing Code');
        $response = eval('return '.$matches[1]);

        if (is_array($response) || is_object($response)) {
            $response = json_decode($response, true);
        }

        $request->updateMessage($message, "```\n{$response}\n```");
    }
}
