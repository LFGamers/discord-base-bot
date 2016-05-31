<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\CoreModule\BotCommand;

use Discord\Base\AbstractBotCommand;
use Discord\Base\Request;
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
    public function setHandlers()
    {
        $this->responds('/^help$/i', [$this, 'renderHelp']);
        $this->responds('/^help (.*)$/i', [$this, 'renderHelpItem']);
    }

    /**
     * @param Request $request
     */
    protected function renderHelp(Request $request)
    {
        $modules = $this->getModuleCommands();
        $request->reply($request->renderTemplate('@Core/help/main.twig'));
        foreach ($modules as $name => $commands) {
            $request->reply(
                $request->renderTemplate(
                    '@Core/help/module.twig',
                    [
                        'module' => [
                            'name'     => str_replace('Module', '', $name),
                            'commands' => $commands,
                        ],
                    ]
                )
            );
        }
    }

    /**
     * @param Request $request
     * @param array   $matches
     */
    protected function renderHelpItem(Request $request, array $matches)
    {
        $modules = $this->getModuleCommands();
        foreach ($modules as $name => $commands) {
            /** @type AbstractBotCommand[] $commands */
            foreach ($commands as $command) {
                if ($command->getName() === $matches[1]) {
                    if (!empty($command->getHelp())) {
                        $request->reply($command->getHelp());
                    }

                    return;
                }
            }
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
