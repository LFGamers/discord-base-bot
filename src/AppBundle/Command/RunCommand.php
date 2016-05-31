<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Command;

use Discord\Base\AbstractModule;
use Discord\Base\AppBundle\Discord;
use Discord\Base\AppBundle\Event\BotEvent;
use Discord\Base\AppBundle\Factory\ServerManagerFactory;
use Discord\Base\AppBundle\Manager\ServerManager;
use Discord\Base\AppBundle\Model\Module;
use Discord\Base\AppBundle\Model\Server;
use Discord\Base\AppBundle\Model\ServerModule;
use Discord\Base\AppBundle\Repository\IgnoredRepository;
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\WebSocket;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class RunCommand extends ContainerAwareCommand
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    
    /**
     * @var SymfonyStyle
     */
    private $output;

    /**
     * @var ServerManager[]
     */
    private $serverManagers;

    /**
     *
     */
    protected function configure()
    {
        $this->setName('discord:run')
            ->setDescription('Runs the Discord Bot');
    }

    /**
     * @return int
     */
    private function getTotalServers()
    {
        $file = $this->getContainer()->getParameter('kernel.cache_dir').'/server_count';
        if (!file_exists($file)) {
            return 0;
        }

        return file_get_contents($file);
    }

    /**
     * @param $servers
     */
    private function updateServerFile($servers)
    {
        $file = $this->getContainer()->getParameter('kernel.cache_dir').'/server_count';
        file_put_contents($file, $servers);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
        $this->output = new SymfonyStyle($input, $output);
        
        $this->dispatcher->dispatch(BotEvent::START, BotEvent::create('START'));

        $shardTitle = $this->getShardTitle();
        $this->output->title(
            (new \DateTime())->format('Y-m-d H:i:s').'Starting '.$this->getContainer()->getParameter('name').$shardTitle
        );

        $this->updateModules();
        $this->fillIgnoredRepository();

        /** @var Discord $discord */
        $discord = $this->getContainer()->get('discord');
        $ws      = $discord->ws;

        $this->dispatcher->dispatch(BotEvent::PREPARE, BotEvent::create('PREPARE'));

        $ws->on('error', [$this, 'logError']);

        $servers  = 0;
        $progress = null;

        $this->output->note('Loading up servers. Please wait.');
        $progress = $this->output->createProgressBar($this->getTotalServers());

        $ws->on(
            'available',
            function () use (&$servers, $progress) {
                $servers++;
                $this->updateServerFile($servers);
                if ($progress !== null) {
                    $progress->advance();
                }
            }
        );

        $ws->on(
            'ready',
            function () use ($ws, $discord, &$servers, $progress) {
                $this->dispatcher->dispatch(BotEvent::READY_START, BotEvent::create('READY_START'));
                
                $this->updateServerFile($servers);
                if ($progress !== null) {
                    $progress->finish();
                    $this->output->newLine(2);
                }

                $this->getContainer()->get('listener.discord')->listen();
                $this->output->success('Bot is ready!');

                $this->createServerManagers();

                $status = $this->getContainer()->getParameter('status');
                if (!empty($status)) {
                    $this->output->note('Setting status to: '.$status);
                    $discord->client->updatePresence($ws, $status, false);
                }

                $this->dispatcher->dispatch(BotEvent::READY_FINISH, BotEvent::create('READY_FINISH'));
            }
        );

        $ws->run();
    }

    private function getShardTitle()
    {
        /** @var Discord $discord */
        $discord = $this->getContainer()->get('discord');
        $options = $discord->client->getOptions();

        if (array_key_exists('shardId', $options)) {
            return ' With shard key: '.json_encode([$options['shardId'], $options['shardCount']]);
        }

        return '';
    }

    /**
     * @param $error
     */
    public function logError($error)
    {
        $this->output->error('Error with websocket: '.$error);

        exit(0);
    }

    private function createServerManagers()
    {
        /*
         * @var Discord
         * @var ObjectManager $manager
         */
        $discord = $this->getContainer()->get('discord');
        $manager = $this->getContainer()->get('default_manager');
        $repo    = $manager->getRepository($this->getContainer()->getParameter('server_class'));

        $servers = $discord->client->guilds;
        $ids     = $servers->map(
            function (Guild $guild) {
                return $guild->id;
            }
        );

        $dbServers = $repo->findBy(['identifier' => $ids->toArray()]);

        $this->output->text('Creating server managers for '.$servers->count().' servers.');
        $this->output->progressStart($servers->count());

        /** @var ServerManagerFactory $factory */
        $factory = $this->getContainer()->get('factory.server_manager');
        foreach ($discord->client->guilds as $server) {
            $dbServer               = $this->findDbServer($dbServers, $server);
            $this->serverManagers[] = $factory->create($server, $dbServer);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $delay = $this->getContainer()->getParameter('database_save_delay');
        if ($delay !== false) {
            $this->getContainer()->get('default_manager')->flush();

            $discord->ws->loop->addPeriodicTimer(
                $delay,
                function () {
                    $this->output->note('Saving current UoW to database.');
                    $this->getContainer()->get('default_manager')->flush();
                }
            );
        }
    }

    /**
     * @param Server[] $dbServers
     * @param Guild    $guild
     *
     * @return Server|mixed|null
     */
    private function findDbServer(array $dbServers, Guild $guild)
    {
        foreach ($dbServers as $server) {
            if ((int) $server->getIdentifier() === (int) $guild->id) {
                return $server;
            }
        }
    }

    private function updateModules()
    {
        $this->output->text('Attempting to updating modules');

        /** @var EntityManager|DocumentManager $manager */
        $manager = $this->getContainer()->get('default_manager');
        $repo    = $manager->getRepository('App:Module');
        foreach ($this->getContainer()->getParameter('kernel.modules') as $module) {
            if (empty($repo->findOneBy(['name' => $module::getModuleName()]))) {
                $this->output->text('New module discovered. Adding to database.');

                $this->addModule($module);
            }
        }

        /** @var Module[] $modules */
        $modules = $manager->getRepository('App:Module')->findAll();
        /** @var Server[] $servers */
        $servers = $manager->getRepository($this->getContainer()->getParameter('server_class'))->findAll();
        foreach ($servers as $server) {
            foreach ($modules as $module) {
                if (!$server->hasModule($module)) {
                    $serverModule = new ServerModule();
                    $serverModule->setModule($module);
                    $serverModule->setServer($server);
                    $serverModule->setEnabled($module->getDefaultEnabled());

                    $manager->persist($serverModule);

                    $server->addModule($serverModule);
                    $manager->persist($server);
                }
            }
        }

        $manager->flush();
    }

    /**
     * @param string|AbstractModule $module Class Name (not the actual class)
     */
    private function addModule($module)
    {
        /** @var EntityManager|DocumentManager $manager */
        $manager = $this->getContainer()->get('default_manager');

        $dbModule = new Module();
        $dbModule->setName($module::getModuleName());
        $dbModule->setDefaultEnabled($module::isDefaultEnabled());
        $dbModule->setDisableable($module::isDisableable());
        $manager->persist($dbModule);

        $manager->flush();
    }

    private function fillIgnoredRepository()
    {
        /** @var EntityManager|DocumentManager $manager */
        $manager = $this->getContainer()->get('default_manager');
        /** @var IgnoredRepository $ignoredRepository */
        $ignoredRepository = $this->getContainer()->get('repository.ignored');

        $repo = $manager->getRepository('App:Ignored');
        foreach ($repo->findAll() as $ignored) {
            $ignoredRepository->add($ignored);
        }
    }

    /**
     *
     */
    private function deleteServerManagers()
    {
        foreach (array_keys($this->serverManagers) as $key) {
            unset($this->serverManagers[$key]);
        }

        $this->serverManagers = [];
    }
}
