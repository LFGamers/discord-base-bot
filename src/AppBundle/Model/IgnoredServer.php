<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Model;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class IgnoredServer extends Ignored
{
    /**
     * @var string
     */
    protected $type = 'server';
}
