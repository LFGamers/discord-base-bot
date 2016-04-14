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

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class Validator
{
    public static function validate(array $configuration)
    {
        self::validateDatabases($configuration['databases']);

        return $configuration;
    }

    private static function validateDatabases($config)
    {
        if (!$config['mysql']['enabled'] && !$config['mongo']['enabled']) {
            throw new \Exception('One of the database types must be enabled for this bot to work. Suggestion: MySQL');
        }

        if ($config['mysql']['enabled'] && $config['mongo']['enabled'] && !isset($config['main'])) {
            throw new \Exception('Define which database you want to store the main data in (mysql or mongo).');
        }

        foreach (['mysql', 'mongo', 'elastic'] as $type) {
            if ($config[$type]['enabled'] && empty($config[$type]['dsn'])) {
                throw new \Exception("Database type '{$type}' is enabled, but has no DSN'");
            }
        }
    }
}
