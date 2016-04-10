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
namespace Discord\Base\CoreBundle\BotCommand;

use Discord\Base\AbstractBotCommand;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class HelpBotCommand extends AbstractBotCommand
{
    public function configure()
    {
        $this->setName('help')
            ->setDescription('Returns the help for the bot.');
    }

    /**
     * @return void
     */
    public function handle()
    {
        $this->responds('/^help$/i', [$this, 'renderHelp']);
    }

    /**
     *
     */
    protected function renderHelp()
    {
        $modules = $this->getModuleCommands();
        $this->reply($this->renderTemplate('@Core/help/main.twig'));
        foreach ($modules as $name => $commands) {
            $this->reply(
                $this->renderTemplate(
                    '@Core/help/module.twig',
                    [
                        'module' => [
                            'name'     => str_replace('Bundle', '', $name),
                            'commands' => $commands,
                        ],
                    ]
                )
            );
        }
    }

    private function getModuleCommands()
    {
        $modules = [];

        /** @var BundleInterface $module */
        foreach ($this->container->get('kernel')->getModules() as $module) {
            $ids = $this->container->getParameter('bot.'.$module->getName().'.commands');

            $modules[$module->getName()] = array_map([$this->container, 'get'], $ids);
        }

        return $modules;
    }
}
