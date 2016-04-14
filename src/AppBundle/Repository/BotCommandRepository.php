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
namespace Discord\Base\AppBundle\Repository;

use Discord\Base\AbstractBotCommand;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class BotCommandRepository
{
    /**
     * @var array|ArrayCollection|AbstractBotCommand[]
     */
    private $botCommands;

    /**
     * BotCommandRepository constructor.
     *
     * @param array|ArrayCollection|AbstractBotCommand[] $botCommands
     */
    public function __construct(array $botCommands)
    {
        $this->botCommands = new ArrayCollection($botCommands);
    }

    /**
     * @return array|ArrayCollection|AbstractBotCommand[]
     */
    public function all()
    {
        return $this->botCommands;
    }
}
