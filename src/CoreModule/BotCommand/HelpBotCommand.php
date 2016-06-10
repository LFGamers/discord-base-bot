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

    private function renderNextHelp(Request $request, array $modules, $count = 5)
    {
        if (count($modules) <= 0) {
            return;
        }

        $message = '';

        for ($i = 0; $i < $count; $i++) {
            list($name, $module) = [key($modules), array_shift($modules)];
            if (empty($name)) {
                break;
            }

            $this->logger->info('Rendering '.$name.' module commands');
            if (empty($module)) {
                return $this->renderNextHelp($request, $modules);
            }

            $message .= $request->renderTemplate(
                '@Core/help/module.twig',
                [
                    'module' => [
                        'name'     => str_replace('Module', '', $name),
                        'commands' => $module,
                    ],
                ]
            );
        }

        $this->logger->info('Length of message: '.strlen($message));
        $request->reply($message)
            ->then(function () use ($request, $modules, $count) {
                $this->renderNextHelp($request, $modules, $count);
            });
    }

    /**
     * @param Request $request
     */
    protected function renderHelp(Request $request)
    {
        $modules = $this->getModuleCommands();
        $request->reply($request->renderTemplate('@Core/help/main.twig'))
            ->then(
                function () use ($modules, $request) {
                    return $this->renderNextHelp($request, $modules);
                }
            );
    }

    /**
     * @param Request $request
     * @param array   $matches
     */
    protected function renderHelpItem(Request $request, array $matches)
    {
        $modules = $this->getModuleCommands();
        foreach ($modules as $name => $commands) {
            /** @var AbstractBotCommand[] $commands */
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
