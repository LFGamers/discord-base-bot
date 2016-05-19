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
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

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
        $this->responds('/^eval( --raw)(?:\s+)```[a-z]*\n([\s\S]*)?\n```$/i', [$this, 'evalCode']);
        $this->responds('/^eval( --raw)(?:\s+)`?([^`]*)?`?$/i', [$this, 'evalCode']);
    }

    /**
     * @param Request $request
     * @param array   $matches
     *
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    protected function evalCode(Request $request, array $matches = [])
    {
        // Lets set some local variables for the eval
        $client    = $this->getDiscord()->client->getClient();
        $webSocket = $this->getDiscord()->ws;
        $container = $this->container;

        $message = $request->reply('Executing Code');

        try {
            if ($matches[1] === '--raw') {
                $response = eval($matches[2]);
            } else {
                $language = new ExpressionLanguage();
                $response = $language->evaluate($matches[2], get_defined_vars());
            }
        } catch (\Exception $e) {
            return $request->updateMessage($message, 'Error executing code: '.$e->getMessage());
        }

        if (is_array($response) || is_object($response)) {
            $response = json_decode($response, true);
        }

        $request->updateMessage($message, "```\n{$response}\n```");
    }
}
