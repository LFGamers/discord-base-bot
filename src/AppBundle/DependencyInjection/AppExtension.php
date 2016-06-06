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

use Discord\Base\AppBundle\Subscriber\ORMMappingSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

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

        if ($container->getParameter('main_database') === 'mysql') {
            $container
                ->setDefinition(
                    'subscriber.mapping.orm',
                    new Definition(ORMMappingSubscriber::class, [$container->getParameter('server_class')])
                )
                ->addTag('doctrine.event_subscriber', ['connection' => 'default']);
        }

        $options  = [
            'token'          => $container->getParameter('token'),
            'loadAllMembers' => true,
            'logger'         => new Reference('monolog.logger.bot'),
            'cachePool'      => new Reference('cache')
        ];
        $sharding = $container->getParameter('sharding');
        if ($sharding['enabled']) {
            $options['shardId']    = (int) $sharding['shardId'];
            $options['shardCount'] = (int) $sharding['shardCount'];
        }

        $container->getDefinition('discord')->setArguments([$options]);
    }
}
