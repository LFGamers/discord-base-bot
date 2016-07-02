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

use Brush\Accounts\Account;
use Brush\Accounts\Credentials;
use Brush\Accounts\Developer;
use Brush\Pastes\Draft;
use Brush\Pastes\Options\Format;
use Brush\Pastes\Options\Visibility;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use GuzzleHttp\Client;
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
     * @var array
     */
    private $pastebin;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $userKey;

    /**
     * ErrorHandler constructor.
     *
     * @param int   $channelId
     * @param array $pastebin
     */
    public function __construct($channelId, $pastebin)
    {
        parent::__construct(Logger::ERROR, true);
        $this->channelId = $channelId;
        $this->pastebin  = $pastebin;
        $this->client    = new Client(['base_uri' => 'http://pastebin.com/api/']);
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
                return;
            }
        }

        $developer = new Developer($this->pastebin['api_key']);
        $account   = new Account(new Credentials($this->pastebin['username'], $this->pastebin['password']));
        $draft     = new Draft();
        $draft->setContent(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $draft->setOwner($account);
        $draft->setVisibility(Visibility::VISIBILITY_UNLISTED);
        $draft->setFormat(Format::fromExtension('js'));
        $draft->setExpiry('24H');
        $draft->setTitle($record['datetime']->format('U'));
        $paste   = $draft->paste($developer);
        $message = substr($record['formatted'], 0, 256);

        $this->channel->sendMessage("Error: \n\n```\n{$message}\n```\n\nPastebin: <{$paste->getUrl()}>");
    }

    private function initialize()
    {
        if (empty($this->userKey)) {
            $response = $this->client->post(
                'api_login.php',
                [
                    'form_params' => [
                        'api_dev_key'       => $this->pastebin['api_key'],
                        'api_user_name'     => $this->pastebin['username'],
                        'api_user_password' => $this->pastebin['password'],
                    ],
                ]
            );

            $this->userKey = (string) $response->getBody();
        }

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
