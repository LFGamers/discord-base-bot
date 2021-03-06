<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Manager;

use Discord\Base\AppBundle\Discord;
use Discord\Base\AppBundle\Event\ServerEvent;
use Discord\Base\AppBundle\Event\ServerManagerLoaded;
use Discord\Base\AppBundle\Model\Module;
use Discord\Base\AppBundle\Model\Server;
use Discord\Base\AppBundle\Model\ServerModule;
use Discord\Base\AppBundle\Repository\BotCommandRepository;
use Discord\Base\Request;
use Discord\Parts\Guild\Guild;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * ServerManager Class
 */
class ServerManager
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var Discord
     */
    protected $discord;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Guild
     */
    protected $clientServer;

    /**
     * @var Server
     */
    protected $databaseServer;

    /**
     * @var BotCommandRepository
     */
    protected $commandRepository;

    /**
     * ServerManager constructor.
     *
     * @param Container $container
     * @param Guild     $guild
     * @param Server    $server
     *
     * @throws \Exception
     */
    public function __construct(Container $container, Guild $guild, Server $server = null)
    {
        $this->container         = $container;
        $this->dispatcher        = $container->get('event_dispatcher');
        $this->discord           = $container->get('discord');
        $this->logger            = $container->get('monolog.logger.bot');
        $this->commandRepository = $container->get('repository.command');

        $this->clientServer   = $guild;
        $this->databaseServer = $server === null ? $this->fetchDatabaseServer() : $server;

        $this->updateServer($this->clientServer);
        $this->dispatcher->addListener(ServerEvent::class, [$this, 'onServerEvent']);

        $this->initialize();
        $this->dispatcher->dispatch('manager.server.loaded', ServerManagerLoaded::create($this));

        if (!$container->getParameter('large')) {
            //$this->logger->debug('Created server manager for: '.$this->clientServer->name);
        }
    }

    /**
     *
     */
    protected function initialize()
    {
    }

    /**
     * @param ServerEvent $event
     */
    public function onServerEvent(ServerEvent $event)
    {
        if ($event->getServer()->getAttribute('id') !== $this->clientServer->getAttribute('id')) {
            return;
        }

        $method = 'on'.ucfirst($event->getType());
        if (method_exists($this, $method)) {
            call_user_func_array([$this, $method], $event->getData());
        }
    }

    /**
     * @param Request $request
     */
    protected function onMessage(Request $request)
    {
        $request->setServerManager($this);

        $isCommand = false;
        foreach ($this->commandRepository->all() as $command) {
            if ($command->handle($request)) {
                $isCommand = true;
                break;
            }
        }

        if ($this->container->getParameter('log_messages') || $isCommand) {
            $this->logger->debug(
                sprintf(
                    '%s <comment>[%s]</comment> <question>[%s/#%s]</question> <comment><@%s></comment> %s',
                    $isCommand ? '<error>[Command]</error>' : '<info>[Message]</info>',
                    (new \DateTime())->format('d/m/y H:i:s A'),
                    $request->isPrivateMessage() ? 'Private Message' : $request->getServer()->name,
                    $request->getChannel()->name,
                    $request->getAuthor()->username,
                    str_replace("\n", '\n', $request->getContent())
                )
            );
        }
    }

    /**
     * @param Guild $clientServer
     */
    public function updateServer(Guild $clientServer)
    {
        if ($this->container->getParameter('database_save_delay') === false) {
            $this->getManager()->persist($this->databaseServer);
            $this->getManager()->flush($this->databaseServer);
        }
    }

    protected function createDatabaseServer(bool $flush = true) : Server
    {
        $cls = $this->container->getParameter('server_class');

        /** @var Server $server */
        $server = new $cls();
        $server->setIdentifier($this->clientServer->getAttribute('id'));
        $server->setOwner($this->clientServer->getOwnerAttribute()->getAttribute('id'));
        $server->setPrefix($this->container->getParameter('prefix'));
        $server->setModules($this->defaultModules($server));

        $this->getManager()->persist($server);

        if ($flush && $this->container->getParameter('database_save_delay') === false) {
            $this->getManager()->flush($server);
        }

        return $server;
    }

    /**
     *
     */
    protected function fetchDatabaseServer()
    {
        $cls = $this->container->getParameter('server_class');

        /** @var Server $server */
        $server = $this->getRepository($cls)
            ->findOneBy(['identifier' => $this->clientServer->getAttribute('id')]);

        if (empty($server)) {
            $server = $this->createDatabaseServer();
        }

        return $server;
    }

    /**
     * @param $model
     *
     * @throws \Exception
     *
     * @return DocumentRepository|EntityRepository
     */
    protected function getRepository($model)
    {
        return $this->getManager()->getRepository($model);
    }

    /**
     * @param Server $server
     *
     * @return array|Module[]
     */
    protected function defaultModules(Server $server)
    {
        $serverModules = [];

        /** @var Module[] $modules */
        $modules = $this->getRepository('App:Module')->findBy(['defaultEnabled' => true]);
        foreach ($modules as $module) {
            $serverModule = new ServerModule();
            $serverModule->setModule($module);
            $serverModule->setServer($server);
            $serverModule->setEnabled($module->getDefaultEnabled());

            $this->getManager()->persist($serverModule);
            $serverModules[] = $serverModule;
        }

        return $serverModules;
    }

    /**
     * @throws \Exception
     *
     * @return DocumentManager|EntityManager
     */
    protected function getManager()
    {
        return $this->container->get('default_manager');
    }

    /**
     * @return Server
     */
    public function getDatabaseServer()
    {
        return $this->databaseServer;
    }

    /**
     * @return Guild
     */
    public function getClientServer()
    {
        return $this->clientServer;
    }
}
