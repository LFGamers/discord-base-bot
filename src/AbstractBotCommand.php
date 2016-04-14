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
use Discord\Parts\Channel\Message;
use RegexGuard\Factory;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractBotCommand
{
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
    protected $adminCommand = false;

    /**
     * @var array
     */
    protected $handlers = [];

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

        $this->configure();
        $this->setHandlers();
    }

    /**
     * @param $key
     *
     * @return Discord
     */
    public function __get($key)
    {
        if ($key === 'client') {
            return $this->getDiscord();
        }

        throw new \InvalidArgumentException;
    }

    /**
     * @return void
     */
    abstract public function setHandlers();

    public function handle(Request $request)
    {
        foreach ($this->handlers as $handler) {
            if ($this->isAdminCommand() && !$request->isAdmin()) {
                continue;
            }

            $type     = $handler['type'];
            $pattern  = $handler['pattern'];
            $callback = $handler['callback'];
            $content  = $request->getContent($type === 'responds');

            $regex   = Factory::getGuard();
            $matched = $regex->match($pattern, $content, $matches);

            if (!$matched) {
                continue;
            }

            $server = $request->getServer();
            $this->logger->debug('Matched '.$this->getName());
            $this->logger->info(
                "Message Received, and matched\n".
                Yaml::dump(
                    [
                        'Message' => [
                            'time'       => (new \DateTime())->format('Y-m-d H:i:s'),
                            'author'     => $request->getAuthor()->username,
                            'server'     => $request->isPrivateMessage() ? null : $server->name,
                            'channel'    => $request->getChannel()->name,
                            'content'    => $request->getContent(),
                            'botMention' => $request->isBotMention(),
                            'pm'         => $request->isPrivateMessage(),
                            'regex'      => $pattern,
                            'matches'    => $matches,
                            'mentions'   => array_map(
                                function ($user) {
                                    return $user->username.' - '.$user->id;
                                },
                                $request->getMentions()
                            ),
                        ],
                    ],
                    4,
                    4
                )
            );

            if ($callback($request, $matches) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function hears($pattern, callable $callback)
    {
        $this->handlers[] = ['type' => 'hears', 'pattern' => $pattern, 'callback' => $callback];
    }

    /**
     * @param string   $pattern
     * @param callable $callback
     *
     * @return bool
     */
    protected function responds($pattern, callable $callback)
    {
        $this->handlers[] = ['type' => 'responds', 'pattern' => $pattern, 'callback' => $callback];
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
    public function isAdminCommand()
    {
        return $this->adminCommand;
    }

    /**
     * @param bool $adminCommand
     *
     * @return AbstractBotCommand
     */
    public function setAdminCommand($adminCommand)
    {
        $this->adminCommand = $adminCommand;

        return $this;
    }

    /**
     * @return Discord
     */
    public function getDiscord()
    {
        return $this->discord;
    }
}
