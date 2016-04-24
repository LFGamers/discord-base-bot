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
     * @param Guild     $server
     */
    public function __construct(Container $container, Guild $server)
    {
        $this->container         = $container;
        $this->dispatcher        = $container->get('event_dispatcher');
        $this->discord           = $container->get('discord');
        $this->logger            = $container->get('monolog.logger.bot');
        $this->commandRepository = $container->get('repository.command');

        $this->clientServer   = $server;
        $this->databaseServer = $this->fetchDatabaseServer();

        $this->updateServer($this->clientServer);
        $this->dispatcher->addListener(ServerEvent::class, [$this, 'onServerEvent']);

        $this->initialize();
        $this->dispatcher->dispatch('manager.server.loaded', ServerManagerLoaded::create($this));
        $this->logger->debug('Created server manager for: '.$this->clientServer->name);
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
        $this->logger->debug("Checking for {$method} method");
        if (method_exists($this, $method)) {
            $this->$method($event->getData());
        }
    }

    /**
     * @param array $data
     */
    protected function onMessage(array $data)
    {
        /** @var Request $request */
        $request = $data['request'];
        $request->setServerManager($this);

        $isCommand = false;
        foreach ($this->commandRepository->all() as $command) {
            $request->processCommand($command);

            if ($request->isHandled()) {
                $isCommand = true;
                continue;
            }
        }

        $this->logger->debug(sprintf(
            '%s <comment>[%s]</comment> <question>[%s/#%s]</question> <comment><@%s></comment> %s',
            $isCommand ? '<error>[Command]</error>' : '<info>[Message]</info>',
            (new \DateTime())->format('d/m/y H:i:s A'),
            $request->isPrivateMessage() ? 'Private Message' : $request->getServer()->name,
            $request->getChannel()->name,
            $request->getAuthor()->username,
            $request->getContent()
        ));
    }

    /**
     * @param Guild $clientServer
     */
    public function updateServer(Guild $clientServer)
    {
        $this->databaseServer->setIdentifier($clientServer->getAttribute('id'));
        $this->databaseServer->setOwner($clientServer->getOwnerAttribute()->getAttribute('id'));

        $this->getManager()->persist($this->databaseServer);
        $this->getManager()->flush($this->databaseServer);
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
            $server = new $cls;
            $server->setIdentifier($this->clientServer->getAttribute('id'));
            $server->setOwner($this->clientServer->getOwnerAttribute()->getAttribute('id'));
            $server->setPrefix($this->container->getParameter('prefix'));
            $server->setModules($this->defaultModules($server));

            $this->getManager()->persist($server);
            $this->getManager()->flush($server);
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
}
