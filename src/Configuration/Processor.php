<?php

/**
 * This file is part of discord-base-bot
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
use Symfony\Component\VarDumper\VarDumper;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class Processor
{
    public static function process(array $configuration)
    {
        $processor = new SymfonyProcessor();

        $processedConfiguration = $processor->processConfiguration(new Configuration, ['config' => $configuration]);

        $validConfig = Validator::validate($processedConfiguration);

        self::prepareDoctrine($validConfig);

        return $validConfig;
    }

    private static function prepareDoctrine(&$config)
    {
        $databases = $config['databases'];
        unset($config['databases']);

        if ($databases['mysql']['enabled']) {
            /** @type MysqlDsn $mysql */
            $mysql = Dsn::parse($databases['mysql']['dsn'])->toArray();

            $mapping = [];
            if (!isset($databases['main']) || $databases['main'] === 'mysql') {
                $mapping = [
                    'AppBundle' => [
                        'type'   => 'yml',
                        'prefix' => 'Discord\Base\AppBundle\Model',
                        'alias'  => 'App'
                    ]
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
                'orm'  => ['auto_mapping' => false, 'mappings' => $mapping]
            ];
        }

        if ($databases['mongo']['enabled']) {
            $mapping = [];
            if (!isset($databases['main']) || $databases['main'] === 'mongo') {
                $mapping = [
                    'AppBundle' => [
                        'type'   => 'yml',
                        'prefix' => 'Discord\Base\AppBundle\Model',
                        'alias'  => 'App'
                    ]
                ];
            }

            $config['doctrine_mongodb'] = [
                'connections'       => [
                    'default' => [
                        'server' => $databases['mongo']['dsn']
                    ],
                ],
                'document_managers' => ['default' => ['auto_mapping' => false, 'mappings' => $mapping]]
            ];
        }
    }
}
