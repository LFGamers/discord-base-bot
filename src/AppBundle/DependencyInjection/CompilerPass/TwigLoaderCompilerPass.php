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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class TwigLoaderCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $modules = [];

        /* @type BundleInterface[] $modules */
        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $ref = new \ReflectionClass($bundle);
            $dir = dirname($ref->getFileName());

            if (file_exists($dir.'/BotCommand')) {
                $container->getDefinition('twig.loader')
                    ->addMethodCall(
                        'addPath',
                        [$dir.'/Resources/views/', str_replace('Bundle', '', $ref->getShortName())]
                    );
            }
        }
    }
}
