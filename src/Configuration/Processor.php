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
 * This file is part of discord-base-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */
namespace Discord\Base\Configuration;

use AD7six\Dsn\Db\MysqlDsn;
use AD7six\Dsn\Dsn;
use Symfony\Component\Config\Definition\Processor as SymfonyProcessor;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class Processor
{
    /**
     * @param array $configuration
     *
     * @return array
     */
    public static function process(array $configuration)
    {
        if (!isset($configuration['cache'])) {
            $configuration['cache'] = self::getDefaultCache();
        }
        $configuration['cache_adapter'] = $configuration['cache'];
        unset($configuration['cache']);

        $processor = new SymfonyProcessor();

        $processedConfiguration = $processor->processConfiguration(new Configuration(), ['config' => $configuration]);

        $validConfig = Validator::validate($processedConfiguration);

        self::prepareDoctrine($validConfig);

        return $validConfig;
    }

    /**
     * @param $config
     *
     * @throws \Exception
     */
    private static function prepareDoctrine(&$config)
    {
        $databases = $config['databases'];
        if (isset($databases['main'])) {
            $config['parameters']['main_database'] = $databases['main'];
        } else {
            $config['parameters']['main_database'] = $databases['mysql']['enabled'] ? 'mysql' : 'mongo';
        }

        unset($config['databases']);

        if ($databases['mysql']['enabled']) {
            /** @var MysqlDsn $mysql */
            $mysql = Dsn::parse($databases['mysql']['dsn'])->toArray();

            $mapping = [];
            if (!isset($databases['main']) || $databases['main'] === 'mysql') {
                $mapping = [
                    'AppBundle' => [
                        'type'   => 'yml',
                        'prefix' => 'Discord\Base\AppBundle\Model',
                        'alias'  => 'App',
                    ],
                ];
            }

            $config['doctrine'] = [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'dbname'   => $mysql['database'],
                            'host'     => $mysql['host'],
                            'port'     => $mysql['port'],
                            'user'     => $mysql['user'],
                            'password' => $mysql['pass'],
                        ],
                    ],
                ],
                'orm'  => ['auto_mapping' => true, 'mappings' => $mapping],
            ];
        }

        if ($databases['mongo']['enabled']) {
            $mapping = [];
            if (!isset($databases['main']) || $databases['main'] === 'mongo') {
                $mapping = [
                    'AppBundle' => [
                        'type'   => 'yml',
                        'prefix' => 'Discord\Base\AppBundle\Model',
                        'alias'  => 'App',
                    ],
                ];
            }

            $config['doctrine_mongodb'] = [
                'connections'       => [
                    'default' => [
                        'server' => $databases['mongo']['dsn'],
                    ],
                ],
                'document_managers' => ['default' => ['auto_mapping' => true, 'mappings' => $mapping]],
            ];
        }
    }

    /**
     * @return array
     */
    private static function getDefaultCache()
    {
        return [
            'providers' => [
                'array' => [
                    'factory' => 'cache.factory.array',
                ],
            ],
        ];
    }
}
