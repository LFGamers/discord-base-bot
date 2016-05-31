<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * BotEvent Class
 */
class BotEvent extends Event
{
    const START       = 'start';

    const PREPARE     = 'prepare';

    const READY_START = 'ready_start';

    const READY_FINISH   = 'ready_finish';

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param string $type
     * @param mixed  $data
     *
     * @return BotEvent
     */
    public static function create($type, ...$data)
    {
        return new self($type, $data);
    }

    /**
     * BotEvent constructor.
     *
     * @param string $type
     * @param array  $data
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
