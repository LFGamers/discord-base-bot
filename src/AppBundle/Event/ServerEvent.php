<?php

/*
 * This file is part of discord-base-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Event;

use Discord\Parts\Guild\Guild;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * ServerManagerLoaded Class
 */
class ServerEvent extends Event
{
    /**
     * @var Guild
     */
    private $server;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $data;

    /**
     * @param Guild  $server
     * @param string $type
     * @param array  $data
     *
     * @return ServerEvent
     */
    public static function create(Guild $server, $type, array $data)
    {
        return new self($server, $type, $data);
    }

    /**
     * ServerEvent constructor.
     *
     * @param Guild  $server
     * @param string $type
     * @param array  $data
     */
    public function __construct(Guild $server, $type, array $data)
    {
        $this->server = $server;
        $this->type   = $type;
        $this->data   = $data;
    }

    /**
     * @return Guild
     */
    public function getServer()
    {
        return $this->server;
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
