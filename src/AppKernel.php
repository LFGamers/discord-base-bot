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

use Discord\Base\AppBundle\AppBundle;
use Discord\Base\CoreBundle\CoreBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Sensio\Bundle\DistributionBundle\SensioDistributionBundle;
use Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class AppKernel extends Kernel
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * @var ContainerBuilder
     */
    private $userContainer;

    /**
     * @var BundleInterface[]
     */
    private $modules;

    /**
     * @return \Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $modules
     *
     * @return AppKernel
     */
    public function setModules($modules)
    {
        $this->modules = $modules;

        return $this;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function setDefaults(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            ['name', 'version', 'modules', 'admin_id', 'token', 'log_dir', 'cache_dir', 'author', 'status', 'prefix']
        );
    }

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function setUserContainer($containerBuilder)
    {
        $this->userContainer = $containerBuilder;
    }

    protected function getContainerBuilder()
    {
        $container = parent::getContainerBuilder();
        if (null !== $this->userContainer) {
            $container->merge($this->userContainer);
        }

        return $container;
    }

    /**
     * @return array
     */
    public function registerBundles()
    {
        $modules = array_merge(
            [new CoreBundle()],
            $this->configuration['modules']
        );

        $this->setModules($modules);

        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new DoctrineBundle(),
            new TwigBundle(),
            new AppBundle(),
        ];

        $bundles = array_merge($bundles, $modules);

        if (in_array($this->getEnvironment(), ['dev'], true)) {
            $bundles[] = new DebugBundle();
            $bundles[] = new SensioDistributionBundle();
            $bundles[] = new SensioGeneratorBundle();
        }

        return $bundles;
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $configDir = $this->getKernelParameters()['kernel.config_dir'];
        $loader->load($configDir.'/config.yml');
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getCacheDir()
    {
        return $this->getRootDir().'/../var/cache';
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getLogDir()
    {
        return $this->getRootDir().'/../var/logs';
    }

    /**
     * @return array
     */
    protected function getKernelParameters()
    {
        $configuration = $this->configuration;
        unset($configuration['modules']);

        $configuration['kernel.modules'] = [];
        foreach ($this->modules as $module) {
            $configuration['kernel.modules'][] = get_class($module);
        }

        return array_merge(
            $configuration,
            [
                'kernel.config_dir' => realpath(__DIR__.'/../config/'),
            ],
            parent::getKernelParameters()
        );
    }
}
