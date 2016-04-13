<?php

/*
 * This file is part of discord-base-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Manager;

use Discord\Base\AppBundle\Discord;
use Discord\Base\AppBundle\Event\ServerManagerLoaded;
use Discord\Base\AppBundle\Model\BaseServer;
use Discord\Base\AppBundle\Model\Module;
use Discord\Base\AppBundle\Model\ServerModule;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\VarDumper\VarDumper;

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
     * @var BaseServer
     */
    protected $databaseServer;

    /**
     * ServerManager constructor.
     *
     * @param Container $container
     * @param Guild     $server
     */
    public function __construct(Container $container, Guild $server)
    {
        $this->container  = $container;
        $this->dispatcher = $container->get('event_dispatcher');
        $this->discord    = $container->get('discord');
        $this->logger     = $container->get('monolog.logger.bot');

        $this->clientServer   = $server;
        $this->databaseServer = $this->fetchDatabaseServer();

        $this->updateBaseServer($this->clientServer);
        $this->createListeners();

        $this->initialize();
        $this->dispatcher->dispatch('manager.server.loaded', ServerManagerLoaded::create($this));
    }

    protected function initialize()
    {
    }

    protected function createListeners()
    {
        $ws = $this->discord->ws;

        $ws->on(Event::GUILD_UPDATE, [$this, 'updateBaseServer']);

        $ws->on(Event::MESSAGE_CREATE, [$this, 'onMessage']);
        $ws->on(Event::GUILD_DELETE, [$this, 'onServerDelete']);
        $ws->on(Event::GUILD_UPDATE, [$this, 'onServerUpdate']);
        $ws->on(Event::CHANNEL_CREATE, [$this, 'onChannelCreate']);
        $ws->on(Event::CHANNEL_UPDATE, [$this, 'onChannelUpdate']);
        $ws->on(Event::CHANNEL_DELETE, [$this, 'onChannelDelete']);
        $ws->on(Event::GUILD_MEMBER_ADD, [$this, 'onMemberCreate']);
        $ws->on(Event::GUILD_MEMBER_UPDATE, [$this, 'onMemberUpdate']);
        $ws->on(Event::GUILD_MEMBER_REMOVE, [$this, 'onMemberDelete']);
    }

    public function onMessage(Message $message)
    {
        $this->logger->info("New Message for {$this->clientServer->name}: " . $message->content);
    }

    public function onServerDelete()
    {
    }

    public function onServerUpdate(Guild $server)
    {
    }

    public function onChannelCreate(Channel $channel)
    {
    }

    public function onChannelUpdate(Channel $channel)
    {
    }

    public function onChannelDelete(Channel $channel)
    {
    }

    public function onMemberCreate(Member $member)
    {
    }

    public function onMemberUpdate(Member $member)
    {
    }

    public function onMemberDelete(Member $member)
    {
    }

    public function updateBaseServer(Guild $clientServer)
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
        $server = $this->getRepository('App:BaseServer')
            ->findOneBy(['identifier' => $this->clientServer->getAttribute('id')]);

        if (empty($server)) {
            $server = new BaseServer();
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
     * @return DocumentRepository|EntityRepository
     * @throws \Exception
     */
    protected function getRepository($model)
    {
        return $this->getManager()->getRepository($model);
    }

    /**
     * @param BaseServer $server
     *
     * @return array|\Discord\Base\AppBundle\Model\Module[]
     */
    protected function defaultModules(BaseServer $server)
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
     * @return DocumentManager|EntityManager
     * @throws \Exception
     */
    protected function getManager()
    {
        return $this->container->get('default_manager');
    }
}
