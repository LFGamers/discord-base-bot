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
namespace Discord\Base;

use Discord\Base\AppBundle\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use RegexGuard\Factory;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Tests\Fixtures\Author;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractBotCommand
{
    /**
     * Max message length.
     */
    const MAX_MESSAGE_LENGTH = 2000;

    /**
     * @var Message
     */
    protected $message;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Discord
     */
    protected $discord;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $help;

    /**
     * @var bool
     */
    protected $admin = false;

    /**
     * @return void
     */
    abstract public function configure();

    /**
     * AbstractBotCommand constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->discord   = $container->get('discord');
        $this->logger    = $container->get('monolog.logger.bot');
        $this->prefix    = $container->getParameter('prefix');
    }

    public function setMessage(Message $message)
    {
        $this->message = $message;
    }

    public function renderTemplate($template, array $parameters = [])
    {
        $parameters = array_merge(['command' => $this], $parameters);

        /** @var TwigEngine $twig */
        $twig = $this->container->get('twig');

        return $twig->render($template, $parameters);
    }

    /**
     * @return void
     */
    abstract public function handle();

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param bool $full
     *
     * @return Channel
     */
    public function getChannel($full = true)
    {
        return $full ? $this->message->full_channel : $this->message->channel;
    }

    /**
     * @return Guild|null
     */
    public function getServer()
    {
        return $this->getChannel()->guild;
    }

    /**
     * @return Author
     */
    public function getAuthor()
    {
        return $this->message->author;
    }

    /**
     * @param bool $mentionless
     *
     * @return string
     */
    public function getContent($mentionless = true)
    {
        if ($mentionless) {
            return str_replace([$this->prefix, $this->getBotMention().' '], '', $this->message->content);
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
     * @return bool
     */
    public function isPrivateMessage()
    {
        return $this->getServer() === null;
    }

    /**
     * @return array|User[]
     */
    public function getMentions()
    {
        return $this->message->mentions;
    }

    private function getMatches($content, $pattern, callable $callback)
    {
        $regex   = Factory::getGuard();
        $matched = $regex->matchAll($pattern, $content, $matches);

        if (!$matched) {
            return false;
        }

        $server = $this->getServer();
        $this->logger->debug('Matched '.$this->getName());
        $this->logger->info(
            "Message Received, and matched\n".
            Yaml::dump(
                [
                    'Message' => [
                        'time'       => (new \DateTime())->format('Y-m-d H:i:s'),
                        'author'     => $this->getAuthor()->username,
                        'server'     => $this->isPrivateMessage() ? null : $server->name,
                        'channel'    => $this->getChannel()->name,
                        'content'    => $this->getContent(),
                        'botMention' => $this->isBotMention(),
                        'pm'         => $this->isPrivateMessage(),
                        'regex'      => $pattern,
                        'matches'    => $matches,
                        'mentions'   => array_map(
                            function ($user) {
                                return $user->username.' - '.$user->id;
                            },
                            $this->getMentions()
                        ),
                    ],
                ],
                4,
                4
            )
        );

        return false !== $callback($matches);
    }

    protected function hears($pattern, callable $callback)
    {
        return $this->getMatches($this->getContent(false), $pattern, $callback);
    }

    /**
     * @param string   $pattern
     * @param callable $callback
     *
     * @return bool
     */
    protected function responds($pattern, callable $callback)
    {
        return $this->getMatches($this->getContent(true), $pattern, $callback);
    }

    /**
     * @param Part $location
     * @param      $message
     * @param int  $delay
     * @param int  $deleteDelay
     *
     * @return mixed;
     */
    protected function sendMessage(Part $location, $message, $delay = 0, $deleteDelay = 0)
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

        if ($location instanceof Channel) {
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
     * @return mixed
     */
    protected function reply($message, $delay = 0, $deleteDelay = 0)
    {
        $location = $this->message->channel;

        return $this->sendMessage($location, $message, $delay, $deleteDelay);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return AbstractBotCommand
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     *
     * @return AbstractBotCommand
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @param mixed $help
     *
     * @return AbstractBotCommand
     */
    public function setHelp($help)
    {
        $this->help = $help;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * @param bool $admin
     *
     * @return AbstractBotCommand
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * @return string
     */
    private function getBotMention()
    {
        return '<@'.$this->discord->client->id.'>';
    }
}
