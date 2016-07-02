<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Logger\Handler;

use Brush\Accounts\Developer;
use Brush\Pastes\Draft;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * ErrorHandler Class
 */
class ErrorHandler extends AbstractProcessingHandler
{
    /**
     * @var Discord
     */
    private $discord;

    /**
     * @var int
     */
    private $channelId;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * ErrorHandler constructor.
     *
     * @param int  $channelId
     * @param bool $pastebinApiKey
     */
    public function __construct($channelId, $pastebinApiKey)
    {
        parent::__construct(Logger::ERROR, true);
        $this->channelId = $channelId;
        $this->pastebin  = new Developer($pastebinApiKey);
    }

    public function setDiscord(Discord $discord)
    {
        $this->discord = $discord;
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     *
     * @return void
     */
    protected function write(array $record)
    {
        if (!$this->initialized) {
            if (!$this->initialize()) {
                echo 'Failed to initialize.';

                return;
            }
        }

        $message = substr($record['message'], 0, 64);
        $draft   = new Draft();
        $draft->setContent(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $paste = $draft->paste($this->pastebin);

        $this->channel->sendMessage("Error: `{$message}` - {$paste->getUrl()}");
    }

    private function initialize()
    {
        if (null === $this->discord->guilds) {
            return false;
        }

        foreach ($this->discord->guilds as $guild) {
            foreach ($guild->channels as $channel) {
                if ($channel->id === $this->channelId) {
                    $this->channel     = $channel;
                    $this->initialized = true;

                    return true;
                }
            }
        }

        return false;
    }
}
