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
 * This file is part of discord-server-list-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */
namespace Discord\Base\AppBundle\Command;

use Discord\WebSockets\WebSocket;
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

        /** @var WebSocket $ws */
        $ws = $this->getContainer()->get('discord')->ws;

        $ws->on('error', [$this, 'logError']);
        //$ws->on('raw', [$this, 'logEvent']);

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
            function () use ($ws, &$servers, $progress) {
                $this->updateServerFile($servers);
                if ($progress !== null) {
                    $progress->finish();
                    $this->output->newLine(2);
                }

                $this->output->success('Bot is ready!');
                $this->getContainer()->get('listener.message')->listen();
                $ws->on('message', [$this, 'onMessage']);
            }
        );

        $ws->run();
    }

    public function onMessage($message)
    {
        $this->output->text("Message from {$message->author->username}: {$message->content}");
    }

    /**
     * @param $error
     */
    public function logError($error)
    {
        $this->output->error($error);
    }

    /**
     * @param $data
     */
    public function logEvent($data)
    {
        echo $data->t.PHP_EOL;
    }
}
