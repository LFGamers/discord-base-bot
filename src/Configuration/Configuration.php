<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('config');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('modules')
                    ->prototype('scalar')->end()
                ->end()
                ->append($this->addDatabaseNode())
                ->arrayNode('parameters')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->isRequired()->end()
                        ->scalarNode('version')->isRequired()->end()
                        ->scalarNode('author')->isRequired()->end()
                        ->scalarNode('token')->isRequired()->end()
                        ->scalarNode('prefix')->defaultValue('%')->end()
                        ->scalarNode('status')->defaultValue('')->end()
                        ->scalarNode('admin_id')->isRequired()->end()
                        ->scalarNode('log_dir')->isRequired()->end()
                        ->scalarNode('cache_dir')->isRequired()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function addDatabaseNode()
    {
        $treeBuilder = new TreeBuilder();
        $node        = $treeBuilder->root('databases');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('main')->values(['mysql', 'mongo'])->end()
                ->arrayNode('mysql')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('dsn')->end()
                    ->end()
                ->end()
                ->arrayNode('mongo')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('dsn')->end()
                    ->end()
                ->end()
                ->arrayNode('redis')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('dsn')->end()
                    ->end()
                ->end()
                ->arrayNode('elastic')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->scalarNode('dsn')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}