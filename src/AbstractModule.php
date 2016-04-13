<?php

/*
 * This file is part of discord-base-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * AbstractModule Class
 */
abstract class AbstractModule extends Bundle
{
    /**
     * @return boolean
     */
    static public function isDefaultEnabled()
    {
        return false;
    }

    /**
     * @return boolean
     */
    static public function isDisableable()
    {
        return true;
    }

    /**
     * @return string
     */
    public static function getModuleName()
    {
        $reflection = new \ReflectionClass(static::class);

        return $reflection->getShortName();
    }

    /**
     * Returns the bundle's container extension.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $extension = $this->createContainerExtension();

            if (null !== $extension) {
                if (!$extension instanceof ExtensionInterface) {
                    throw new \LogicException(
                        sprintf(
                            'Extension %s must implement Symfony\Component\DependencyInjection\Extension\ExtensionInterface.',
                            get_class($extension)
                        )
                    );
                }

                // check naming convention
                $basename = preg_replace('/Module$/', '', $this->getName());
                $expectedAlias = Container::underscore($basename);

                if ($expectedAlias != $extension->getAlias()) {
                    throw new \LogicException(
                        sprintf(
                            'Users will expect the alias of the default extension of a module to be the underscored version of the module name ("%s"). You can override "AbstractModule::getContainerExtension()" if you want to use "%s" or another alias.',
                            $expectedAlias,
                            $extension->getAlias()
                        )
                    );
                }

                $this->extension = $extension;
            } else {
                $this->extension = false;
            }
        }

        if ($this->extension) {
            return $this->extension;
        }
    }

    /**
     * Returns the module's container extension class.
     *
     * @return string
     */
    protected function getContainerExtensionClass()
    {
        $basename = preg_replace('/Module$/', '', $this->getName());

        return $this->getNamespace().'\\DependencyInjection\\'.$basename.'Extension';
    }
}
