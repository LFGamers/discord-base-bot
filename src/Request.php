<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base;

use Discord\Base\AppBundle\Manager\ServerManager;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Monolog\Logger;
use React\Promise\Deferred;
use React\Promise\Promise;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Twig_Environment;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * Request Class
 */
class Request
{
    /**
     * Max message length.
     */
    const MAX_MESSAGE_LENGTH = 2000;

    /**
     * @var Discord
     */
    private $discord;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TwigEngine
     */
    private $twig;

    /**
     * @var string
     */
    private $adminId;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var Message
     */
    private $message;

    /**
     * @var bool
     */
    private $handled;

    /**
     * @var Member
     */
    private $guildAuthor;

    /**
     * @var ServerManager
     */
    private $serverManager;

    /**
     * @var bool
     */
    private $interactive = true;

    /**
     * Request constructor.
     *
     * @param Discord          $discord
     * @param Logger           $logger
     * @param Twig_Environment $twig
     * @param string           $adminId
     * @param string           $prefix
     * @param Message          $message
     */
    public function __construct(
        Discord $discord,
        Logger $logger,
        Twig_Environment $twig,
        $adminId,
        $prefix,
        Message $message
    ) {
        $this->discord = $discord;
        $this->logger  = $logger;
        $this->twig    = $twig;
        $this->adminId = $adminId;
        $this->prefix  = $prefix;
        $this->message = $message;
    }

    /**
     * @param bool $interactive
     */
    public function setInteractive($interactive)
    {
        $this->interactive = $interactive;
    }

    /**
     * @param AbstractBotCommand $command
     */
    public function processCommand(AbstractBotCommand $command)
    {
        if (!$this->interactive) {
            return;
        }

        $this->handled = $command->handle($this);
    }

    /**
     * @return bool
     */
    public function isHandled()
    {
        return $this->handled;
    }

    /**
     * @param       $template
     * @param array $parameters
     *
     * @throws \Twig_Error
     *
     * @return string
     */
    public function renderTemplate($template, array $parameters = [])
    {
        $parameters = array_merge(['request' => $this], $parameters);

        return $this->twig->render($template, $parameters);
    }

    /**
     * @param Part   $location
     * @param string $message
     * @param int    $delay
     * @param int    $deleteDelay
     *
     * @return Promise
     */
    public function sendMessage(Part $location, $message, $delay = 0, $deleteDelay = 0)
    {
        $deferred = new Deferred();
        if (!$this->interactive) {
            return $deferred->reject();
        }

        if (strlen($message) > static::MAX_MESSAGE_LENGTH) {
            return $deferred->reject('Message is too long');
        }

        if ($delay > 0) {
            return $this->discord->loop->addTimer(
                $delay,
                function () use ($location, $message, $deleteDelay, $deferred) {
                    $this->sendMessage($location, $message, 0, $deleteDelay)
                        ->otherwise([$deferred, 'reject'])
                        ->then([$deferred, 'resolve']);
                }
            );
        }

        if ($location instanceof Channel || $location instanceof User) {
            $location->sendMessage($message)
                ->otherwise([$deferred, 'reject'])
                ->then(
                    function (Message $message) use ($deleteDelay, $deferred) {
                        if ($message !== false && $deleteDelay > 0) {
                            $this->discord->loop->addTimer(
                                $deleteDelay,
                                function () use ($message) {
                                    $channel = $message->channel;
                                    $channel->messages->delete($message);
                                }
                            );
                        }

                        $deferred->resolve($message);
                    }
                );

            return $deferred->promise();
        }

        throw new \InvalidArgumentException('Location is not a valid place to send a message.');
    }

    /**
     * @param string $message
     * @param int    $delay
     * @param int    $deleteDelay
     *
     * @return Promise
     */
    public function reply($message, $delay = 0, $deleteDelay = 0)
    {
        $location = $this->isPrivateMessage() ? $this->getAuthor() : $this->getChannel();

        return $this->sendMessage($location, $message, $delay, $deleteDelay);
    }

    /**
     * @param Message $message
     * @param string  $content
     *
     * @throws \Discord\Exceptions\PartRequestFailedException
     *
     * @return Message
     */
    public function updateMessage(Message $message, string $content)
    {
        $deferred = new Deferred();

        $message->content = $content;
        $this->getChannel()->messages->save($message)->then([$deferred, 'resolve'])->otherwise([$deferred, 'reject']);

        return $deferred->promise();
    }

    /**
     * @param int|float $interval
     * @param callable  $callback
     *
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    public function runAfter($interval, callable $callback)
    {
        return $this->discord->loop->addTimer($interval, $callback);
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return Guild|null
     */
    public function getServer()
    {
        return $this->discord->guilds->get('id', $this->getChannel()->guild_id);
    }

    /**
     * @return User|Member
     */
    public function getAuthor()
    {
        return $this->message->author;
    }

    /**
     * @return Discord
     */
    public function getDiscord()
    {
        return $this->discord;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param bool $full
     *
     * @return Channel
     */
    public function getChannel()
    {
        return $this->message->channel;
    }

    /**
     * @return bool
     */
    public function isPrivateMessage()
    {
        return $this->getServer() === null;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->getAuthor()->id === (string) $this->adminId;
    }

    /**
     * @param bool $mentionless
     *
     * @return string
     */
    public function getContent($mentionless = true)
    {
        if ($mentionless) {
            $dbServer = $this->getDatabaseServer();
            $prefix   = $dbServer === null ? $this->prefix : $dbServer->getPrefix();

            if (strpos($this->message->content, $prefix) === 0) {
                return substr($this->message->content, strlen($prefix));
            }

            if (strpos($this->message->content, $this->getBotMention() . ' ') !== false) {
                return substr($this->message->content, strlen($this->getBotMention()) + 1);
            }
        }

        return $this->message->content;
    }

    /**
     * @return bool
     */
    public function isBotMention()
    {
        if ($this->isPrivateMessage()) {
            return true;
        }

        // If the content starts with the prefix, its a mention
        $dbServer = $this->getDatabaseServer();
        $prefix   = $dbServer === null ? $this->prefix : $dbServer->getPrefix();
        if (strpos($this->getContent(false), $prefix) === 0) {
            return true;
        }

        // If the content starts with a mention to the bot, its a mention.... Duh.
        if (strpos($this->getContent(false), $this->getBotMention()) === 0) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isPm()
    {
        return $this->isPrivateMessage();
    }

    /**
     * @return array|User[]
     */
    public function getMentions()
    {
        return $this->getMessage()->mentions;
    }

    /**
     * @param int $index
     *
     * @return User
     */
    public function getMention($index)
    {
        return $this->getMentions()[$index];
    }

    /**
     * @return string
     */
    public function getBotMention()
    {
        return '<@'.$this->discord->id.'>';
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @return TwigEngine
     */
    public function getTwig()
    {
        return $this->twig;
    }

    /**
     * @return string
     */
    public function getAdminId()
    {
        return $this->adminId;
    }

    /**
     * @return ServerManager
     */
    public function getServerManager()
    {
        return $this->serverManager;
    }

    /**
     * @param ServerManager $serverManager
     *
     * @return Request
     */
    public function setServerManager($serverManager)
    {
        $this->serverManager = $serverManager;

        return $this;
    }

    /**
     * @return AppBundle\Model\Server|null
     */
    public function getDatabaseServer()
    {
        if (null === $this->serverManager) {
            return;
        }

        return $this->serverManager->getDatabaseServer();
    }

    /**
     * @param Message $message
     *
     * @return Promise
     */
    public function deleteMessage(Message $message)
    {
        $deferred = new Deferred();

        $this->getChannel()->messages->delete($message)->then([$deferred, 'resolve'])->otherwise([$deferred, 'reject']);

        return $deferred->promise();
    }

    /**
     * @param Member $member
     */
    public function setGuildAuthor(Member $member)
    {
        $this->guildAuthor = $member;
    }

    /**
     * @return Member
     */
    public function getGuildAuthor()
    {
        return $this->guildAuthor;
    }
}
