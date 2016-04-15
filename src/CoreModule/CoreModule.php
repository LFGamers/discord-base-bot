<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\CoreModule;

use Discord\Base\AbstractModule;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class CoreModule extends AbstractModule
{
    /**
     * @return bool
     */
    public static function isDefaultEnabled()
    {
        return true;
    }

    /**
     * @return bool
     */
    public static function isDisableable()
    {
        return false;
    }
}
