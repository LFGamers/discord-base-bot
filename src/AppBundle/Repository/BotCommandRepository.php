<?php

/**
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\VarDumper\VarDumper;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class BotCommandRepository
{
    /**
     * @var array|ArrayCollection
     */
    private $botCommands;

    /**
     * BotCommandRepository constructor.
     *
     * @param array|ArrayCollection $botCommands
     */
    public function __construct(array $botCommands)
    {
        $this->botCommands = new ArrayCollection($botCommands);
    }

    /**
     * @return array|ArrayCollection
     */
    public function all()
    {
        return $this->botCommands;
    }
}
