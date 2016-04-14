<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

use Discord\Base\Bot;
use Doctrine\Common\Annotations\AnnotationRegistry;

/*
 * @var Composer\Autoload\ClassLoader
 */
$loader = require __DIR__.'/../vendor/autoload.php';
AnnotationRegistry::registerLoader([$loader, 'loadClass']);

ini_set('memory_limit', '-1');
set_time_limit(0);
//umask(0000);

$bot = Bot::create(
    [
        'modules'    => [],
        'parameters' => [
            'name'      => 'Test Bot',
            'version'   => '0.0.1',
            'author'    => 'Aaron',
            'log_dir'   => __DIR__.'/../var/logs/',
            'cache_dir' => __DIR__.'/../var/cache/',
            'admin_id'  => getenv('ADMIN_ID'),
            'token'     => getenv('TOKEN'),
            'prefix'    => '%',
            'status'    => 'Discord Base Bot',
        ],
        'cache'      => [
            'providers' => [
                'chain' => [
                    'factory' => 'cache.factory.chain',
                    'options' => [
                        'services' => [
                            '@cache.provider.array',
                            '@cache.provider.redis',
                        ],
                    ],
                ],
                'array' => [
                    'factory' => 'cache.factory.array',
                ],
                'redis' => [
                    'factory' => 'cache.factory.redis',
                    'options' => ['dsn' => getenv('REDIS_DSN')]
                ],
            ],
        ],
        'databases'  => [
            'main'  => getenv('MAIN_DATABASE'),
            'mysql' => [
                'enabled' => !empty(getenv('MYSQL_DSN')),
                'dsn'     => getenv('MYSQL_DSN'),
            ],
            'mongo' => [
                'enabled' => !empty(getenv('MONGO_DSN')),
                'dsn'     => getenv('MONGO_DSN'),
            ],
        ],
    ]
);

$bot->run();
