# Discord Server List Bot

Welcome to the [DiscordPHP][] based "BaseBot". This library is a foundation for your own PHP-Based Discord Bot. This library
built on the Symfony 2 framework.

## Requirements

* PHP ^5.6|^7.0
* Mysql OR Mongo
* [Composer](http://getcomposer.org)
* A Discord Bot Account (and its token)

## Installation

This library can be installed with composer:

```sh
composer require lfgamers/discord-base-bot
```

## Usage

To get a super basic bot running, you will have to create a module, and your module will have to  set up a Server class
that extends `Discord\Base\AppBundle\Model\Server`, and then create a simple entry script like the following:
 
```php
<?php
 
use Discord\Base\Bot;
 
require __DIR__.'/../vendor/autoload.php';
 
$bot = Bot::create(
    [
        'modules'    => [
            MyModule::class
        ],
        'parameters' => [
            'name'        => 'MyFirstDiscordBot',
            'version'     => '0.0.1',
            'author'      => 'AwesomePerson',
            'log_dir'     => __DIR__.'/var/logs/',
            'cache_dir'   => __DIR__.'/var/cache/',
            'admin_id'    => 'MyDiscordUserAccountId',
            'token'       => 'MyBotToken',
            'prefix'      => '%',
            'status'      => 'with My Awesome Discord Bot',
            'server_class' => MyModule\Model\Server::class,
        ],
        'databases'  => [
            'mysql' => [
                'enabled' => true,
                'dsn'     => 'mysql://localhost/database',
            ],
        ],
    ]
);

$bot->run();
```

If you haven't run the bot before, you have to set up the schema with:

`php bot.php doctrine:scehma:create`

Then, run that file with the arguments `discord:run` to start the bot.

### Modules

To add your own commands and code, you will need to make your own `Module` (which is just an extension of Symfony's Bundle class),
and then create `BotCommand`'s that extend [AbstractBotCommand][].

Your directory structure should look something like (The directory for bot commands must be named "BotCommand", and commands 
must be suffixed with "BotCommand"):

```
src
  BotCommand
    MyBotCommand.php
  MyModule.php
````

[DiscordPHP]: https://www.github.com/teamreflex/DiscordPHP
[AbstractBotCommand]: src/AbstractBotCommand.php
