<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Model\Ignored;

use Discord\Base\AppBundle\Model\Ignored;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class IgnoredChannel extends Ignored
{
    /**
     * @var string
     */
    protected $type = 'channel';
}
