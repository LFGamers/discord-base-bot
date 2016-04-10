<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle;

use Discord\Base\AppBundle\DependencyInjection\CompilerPass\BotCommandCompilerPass;
use Discord\Base\AppBundle\DependencyInjection\CompilerPass\TwigLoaderCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new BotCommandCompilerPass());
        $container->addCompilerPass(new TwigLoaderCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
