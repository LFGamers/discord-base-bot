<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class AppExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $location = __DIR__.'/../Resources/config/services';
        $loader   = new XmlFileLoader($container, new FileLocator($location));
        foreach (glob($location.'/*.xml') as $file) {
            $loader->load($file);
        }

        $container->setAlias(
            'default_manager',
            $container->getParameter('main_database') === 'mysql'
                ? 'doctrine.orm.default_entity_manager'
                : 'doctrine_mongodb.odm.default_document_manager'
        );
        
        $sharding = $container->getParameter('sharding');
        if ($sharding['enabled']) {
            $container->getDefinition('discord')
                ->addArgument($sharding['shardId'])
                ->addArgument($sharding['shardCount']);
        }
    }
}
