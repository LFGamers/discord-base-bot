<?php

/*
 * This file is part of discord-server-list-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base;

use Aequasi\Environment\SymfonyEnvironment;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Debug\Debug;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Bot
{
    /**
     * @var AppKernel
     */
    private $kernel;

    public static function create(array $configuration, ContainerBuilder $containerBuilder = null)
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
        $resolver = new OptionsResolver();
        $this->setDefaults($resolver);

        $configuration = $resolver->resolve($configuration);

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

    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function setDefaults(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            ['name', 'version', 'modules', 'admin_id', 'token', 'log_dir', 'cache_dir', 'author']
        );

        $resolver->setDefault('status', '');
        $resolver->setDefault('prefix', '!');
    }
}
