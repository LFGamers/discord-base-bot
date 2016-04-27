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

use Cache\AdapterBundle\CacheAdapterBundle;
use Discord\Base\AppBundle\AppBundle;
use Discord\Base\CoreModule\CoreModule;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Sensio\Bundle\DistributionBundle\SensioDistributionBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class AppKernel extends Kernel
{
    use MicroKernelTrait;

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
     * @param AbstractModule[] $modules
     *
     * @throws \Exception
     *
     * @return AppKernel
     */
    public function setModules(array $modules)
    {
        foreach ($modules as $index => $module) {
            $cls = new $module();
            if (!($cls instanceof AbstractModule)) {
                throw new \Exception("{$module} does not extend ".AbstractModule::class);
            }

            $modules[$index] = $cls;
        }

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
        $this->setModules(
            array_merge(
                [CoreModule::class],
                $this->configuration['modules']
            )
        );

        $bundles = array_merge(
            [
                new FrameworkBundle(),
                new MonologBundle(),
                new DoctrineBundle(),
                new TwigBundle(),
                new CacheAdapterBundle(),
                new AppBundle(),
            ],
            $this->configuration['bundles']
        );

        if (class_exists(DoctrineMongoDBBundle::class) && array_key_exists('doctrine_mongodb', $this->configuration)) {
            $bundles[] = new DoctrineMongoDBBundle();
        }

        $bundles = array_merge($bundles, $this->modules);

        if (in_array($this->getEnvironment(), ['dev'], true)) {
            $bundles[] = new DebugBundle();
            $bundles[] = new SensioDistributionBundle();
        }

        return $bundles;
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getCacheDir()
    {
        return $this->configuration['parameters']['cache_dir'];
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getLogDir()
    {
        return $this->configuration['parameters']['log_dir'];
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        // No Routes
    }

    /**
     * Configures the container.
     *
     * You can register extensions:
     *
     * $c->loadFromExtension('framework', array(
     *     'secret' => '%secret%'
     * ));
     *
     * Or services:
     *
     * $c->register('halloween', 'FooBundle\HalloweenProvider');
     *
     * Or parameters:
     *
     * $c->setParameter('halloween', 'lot of fun');
     *
     * @param ContainerBuilder $c
     * @param LoaderInterface  $loader
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.config_dir', realpath(__DIR__.'/../config/'));
        $configDir = $c->getParameter('kernel.config_dir');
        $loader->load($configDir.'/config.yml');

        $configuration = $this->configuration;
        unset($configuration['modules']);
        unset($configuration['bundles']);

        $c->setParameter('kernel.modules', array_map('get_class', $this->modules));

        foreach ($configuration as $key => $values) {
            if ($key === 'parameters') {
                foreach ($values as $name => $value) {
                    $c->setParameter($name, $value);
                }
            } else {
                $c->loadFromExtension($key, $values);
            }
        }
    }
}
