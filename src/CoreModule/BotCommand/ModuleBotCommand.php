<?php
/**
 * This file is part of discord-base-bot
 *
 * Copyright (c) 2016 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

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
namespace Discord\Base\CoreModule\BotCommand;

use Discord\Base\AbstractBotCommand;
use Discord\Base\AppBundle\Model\BaseServer;
use Discord\Base\AppBundle\Model\ServerModule;
use Discord\Base\Request;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class ModuleBotCommand extends AbstractBotCommand
{
    /**
     *
     */
    public function configure()
    {
        $this->setName('module')
            ->setDescription('Configure modules for the bot.')
            ->setAdminCommand(true)
            ->setHelp(
                <<<EOF
The module command lets you list, enable, and disable modules for the given server

`module list` Lists all module statuses for the current server
`module enable <module>` Enables the given module on the current server 
`module disable <module>` Disables the given module on the current server 
EOF
            );
    }

    /**
     * @return void
     */
    public function setHandlers()
    {
        $this->responds(
            '/^module$/i',
            function (Request $request) {
                $request->reply($this->getHelp());
            }
        );

        $this->responds('/^module list$/i', [$this, 'renderModuleList']);
        $this->responds('/^module (enable|disable) ([A-Za-z0-9]+)$/', [$this, 'toggleModule']);
    }

    /**
     * @param Request $request
     */
    protected function renderModuleList(Request $request)
    {
        $request->reply(
            $request->renderTemplate(
                '@Core/module/list.twig',
                [
                    'server' => $request->getServerManager()->getDatabaseServer()
                ]
            )
        );
    }

    /**
     * @param Request $request
     * @param array   $matches
     */
    protected function toggleModule(Request $request, array $matches)
    {
        if ($serverModule = $this->getServerModule($request, $matches)) {
            $serverModule->setEnabled($matches[1] === 'enabled');
            $this->getManager()->flush();
        }
    }

    /**
     * @param Request $request
     * @param array   $matches
     *
     * @return bool|ServerModule|mixed|null
     */
    private function getServerModule(Request $request, array $matches)
    {
        $moduleName   = $matches[2];
        $server       = $request->getServerManager()->getDatabaseServer();
        $serverModule = null;

        foreach ($server->getModules() as $item) {
            $name = strtolower(str_replace('Module', '', $item->getModule()->getName()));
            if ($name === strtolower($moduleName)) {
                $serverModule = $item;
                break;
            }
        }

        if (empty($serverModule)) {
            $request->reply("\"{$moduleName}\" is not a valid module name.");

            return false;
        }

        if (!$serverModule->getModule()->isDisableable()) {
            $request->reply("\"{$moduleName}\" is not disableable.");

            return false;
        }

        return $serverModule;
    }
}
