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
use Discord\Parts\Channel\Message;
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
        $this->responds('/^eval( --raw)?(?:\s+)```[a-z]*\n([\s\S]*)?\n```$/i', [$this, 'evalCode']);
        $this->responds('/^eval( --raw)?(?:\s+)`?([^`]*)?`?$/i', [$this, 'evalCode']);
    }

    /**
     * @param Request $request
     * @param array   $matches
     *
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    protected function evalCode(Request $request, array $matches = [])
    {
        $request->deleteMessage($request->getMessage())
            ->then(
                function () use ($request, $matches) {
                    $request->reply('Executing Code')
                        ->then(
                            function (Message $message) use ($request, $matches) {
                                // Lets set some local variables for the eval
                                $client    = $this->getDiscord();
                                $container = $this->container;
                                $server    = $request->getServer();
                                $author    = $request->getAuthor();
                                $channel   = $request->getChannel();

                                $start                = microtime(true);
                                $_____responseContent = <<<'EOF'
```php
# Executed the following code in %d ms
%s

# Resulted in the following:
%s

```
EOF;

                                $sprintf   = [];
                                $sprintf[] = $matches[2];

                                try {
                                    if ($matches[1] === ' --raw') {
                                        $response = eval($matches[2]);
                                        var_dump($response);
                                    } else {
                                        $language   = new ExpressionLanguage();
                                        $sprintf[0] = $language->compile($matches[2], array_keys(get_defined_vars()))
                                            .' ('.$matches[2].')';
                                        $response   = @$language->evaluate($matches[2], get_defined_vars());
                                    }
                                } catch (\Exception $e) {
                                    var_dump($e);
                                    $sprintf[] = $e->getMessage().' on line '.$e->getLine().' in file '.$e->getFile();
                                    $sprintf[] = (microtime(true) - $start) * 1000;

                                    $request->updateMessage(
                                        $message,
                                        sprintf($_____responseContent, $sprintf[2], $sprintf[0], $sprintf[1])
                                    );
                                }

                                if (is_array($response) || is_object($response)) {
                                    $response = json_decode($response, true);
                                }

                                $sprintf[] = $response;
                                $sprintf[] = (microtime(true) - $start) * 1000;

                                $request->updateMessage(
                                    $message,
                                    sprintf($_____responseContent, $sprintf[2], $sprintf[0], $sprintf[1])
                                );
                            }
                        );
                }
            );
    }
}
