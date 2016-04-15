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
use Discord\Base\AppBundle\Model\BaseServer;
use Discord\Base\AppBundle\Model\Module;
use Discord\Base\AppBundle\Model\ServerModule;
use Discord\Base\AppBundle\Repository\IgnoredRepository;
use Discord\WebSockets\WebSocket;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class RunCommand extends ContainerAwareCommand
{
    /**
     * @var SymfonyStyle
     */
    private $output;

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
        $this->output = new SymfonyStyle($input, $output);

        $this->output->title('Starting '.$this->getContainer()->getParameter('name'));

        $this->updateModules();
        $this->fillIgnoredRepository();

        /*
         * @var Discord
         * @var WebSocket $ws
         */
        $discord = $this->getContainer()->get('discord');
        $ws      = $discord->ws;

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
                $this->updateServerFile($servers);
                if ($progress !== null) {
                    $progress->finish();
                    $this->output->newLine(2);
                }

                $this->output->success('Bot is ready!');

                $this->getContainer()->get('listener.discord')->listen();
                $this->createServerManagers();

                $status = $this->getContainer()->getParameter('status');
                if (!empty($status)) {
                    $this->output->note("Setting status to: " . $status);
                    $discord->client->updatePresence($ws, $status, false);
                }
            }
        );

        $ws->run();
    }

    /**
     * @param $error
     */
    public function logError($error)
    {
        $this->output->error($error);
    }

    private function createServerManagers()
    {
        /** @var Discord $discord */
        $discord = $this->getContainer()->get('discord');

        $factory = $this->getContainer()->get('factory.server_manager');
        foreach ($discord->client->guilds as $server) {
            $factory->create($server);
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
        /** @var BaseServer[] $servers */
        $servers = $manager->getRepository('App:BaseServer')->findAll();
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
}
