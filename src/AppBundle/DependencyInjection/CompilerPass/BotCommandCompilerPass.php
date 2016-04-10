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
namespace Discord\Base\AppBundle\DependencyInjection\CompilerPass;

use Discord\Base\AbstractBotCommand;
use Discord\Base\AppBundle\Repository\BotCommandRepository;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class BotCommandCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $botCommands = [];

        /** @var BundleInterface[] $modules */
        $modules = $container->getParameter('kernel.modules');
        foreach ($modules as $name => $module) {
            $ref = new \ReflectionClass($module);
            $dir = dirname($ref->getFileName());

            $finder = new Finder();
            $finder->files()->name('*BotCommand.php')->in($dir);

            $moduleCommands = [];
            $prefix         = $ref->getNamespaceName();
            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $ns = $prefix;
                if ($relativePath = $file->getRelativePath()) {
                    $ns .= '\\'.str_replace('/', '\\', $relativePath);
                }
                $class = $ns.'\\'.$file->getBasename('.php');

                $reflection = new \ReflectionClass($class);
                if ($this->isValidBotCommand($reflection)) {
                    $id = 'bot.command.'.strtolower(str_replace('\\', '_', $class));
                    $container->register($id, $class)->addArgument(new Reference('service_container'))
                        ->addTag('bot_command');
                    $botCommands[]    = new Reference($id);
                    $moduleCommands[] = $id;
                }
            }

            $container->setParameter('bot.'.$ref->getShortName().'.commands', $moduleCommands);
        }

        $container->register('repository.command', BotCommandRepository::class)
            ->setArguments([$botCommands]);
    }

    /**
     * @param \ReflectionClass $reflection
     *
     * @return bool|AbstractBotCommand
     */
    private function isValidBotCommand(\ReflectionClass $reflection)
    {
        if (!$reflection->isSubclassOf(AbstractBotCommand::class)) {
            return false;
        }

        if ($reflection->isAbstract()) {
            return false;
        }

        if ($reflection->getConstructor()->getNumberOfRequiredParameters() !== 1) {
            return false;
        }

        return true;
    }
}
