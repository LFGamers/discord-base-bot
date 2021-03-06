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

use Aequasi\Environment\SymfonyEnvironment;
use Discord\Base\Configuration\Processor;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Debug\Debug;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Bot
{
    /**
     * @var AppKernel
     */
    private $kernel;

    /**
     * @param array                 $configuration
     * @param ContainerBuilder|null $containerBuilder
     *
     * @return Application
     */
    public static function create(array $configuration, ContainerBuilder $containerBuilder = null) : Application
    {
        $bot         = new static($configuration, $containerBuilder);
        $application = new Application($bot->getKernel());

        return $application;
    }

    /**
     * Bot constructor.
     *
     * @param array            $configuration
     * @param ContainerBuilder $containerBuilder
     */
    public function __construct(array $configuration, ContainerBuilder $containerBuilder = null)
    {
        $configuration = Processor::process($configuration);
        ini_set('memory_limit', $configuration['parameters']['memory_limit']);
        set_time_limit(0);

        $env = new SymfonyEnvironment();
        if ($env->isDebug()) {
            Debug::enable();
        }

        $kernel = new AppKernel($env->getType(), $env->isDebug());
        $kernel->setConfiguration($configuration);
        if ($containerBuilder !== null) {
            $kernel->setUserContainer($containerBuilder);
        }

        $this->kernel = $kernel;
    }

    public function getKernel() : AppKernel
    {
        return $this->kernel;
    }
}
