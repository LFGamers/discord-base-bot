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

use Discord\Base\AppBundle\Discord;
use Discord\Base\AppBundle\Manager\ServerManager;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Monolog\Logger;
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
     * @var ServerManager
     */
    private $serverManager;

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
     * @param AbstractBotCommand $command
     */
    public function processCommand(AbstractBotCommand $command)
    {
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
     * @return mixed;
     */
    public function sendMessage(Part $location, $message, $delay = 0, $deleteDelay = 0)
    {
        if (strlen($message) > static::MAX_MESSAGE_LENGTH) {
            return $this->logger->error('Message is too long');
        }

        if ($delay > 0) {
            return $this->discord->ws->loop->addTimer(
                $delay,
                function () use ($location, $message, $deleteDelay) {
                    $this->sendMessage($location, $message, 0, $deleteDelay);
                }
            );
        }

        if ($location instanceof Channel || $location instanceof User) {
            $message = $location->sendMessage($message);

            if ($message !== false && $deleteDelay > 0) {
                $this->discord->ws->loop->addTimer($deleteDelay, [$message, 'delete']);
            }

            return $message;
        }

        throw new \InvalidArgumentException('Location is not a valid place to send a message.');
    }

    /**
     * @param string $message
     * @param int    $delay
     * @param int    $deleteDelay
     *
     * @return Message
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
    public function updateMessage(Message $message, $content)
    {
        $message->content = $content;
        $message->save();

        return $message;
    }

    /**
     * @param int|float $interval
     * @param callable  $callback
     *
     * @return \React\EventLoop\Timer\Timer|\React\EventLoop\Timer\TimerInterface
     */
    public function runAfter($interval, callable $callback)
    {
        return $this->discord->ws->loop->addTimer($interval, $callback);
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
        return $this->getChannel()->getGuildAttribute();
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->message->getAuthorAttribute();
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
    public function getChannel($full = true)
    {
        return $full ? $this->message->getFullChannelAttribute() : $this->message->getChannelAttribute();
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
        return (string) $this->getAuthor()->getAttribute('id') === $this->adminId;
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

            return str_replace([$prefix, $this->getBotMention().' '], '', $this->message->content);
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
        if (strpos($this->getContent(false), $this->prefix) === 0) {
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
        return '<@'.$this->discord->client->id.'>';
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
     * @return AppBundle\Model\BaseServer|null
     */
    public function getDatabaseServer()
    {
        if (null === $this->serverManager) {
            return;
        }

        return $this->serverManager->getDatabaseServer();
    }
}
