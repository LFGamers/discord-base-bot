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
class ServerModule
{
    /**
     * @var int|\MongoId
     */
    protected $id;

    /**
     * @var BaseServer
     */
    protected $server;

    /**
     * @var Module
     */
    protected $module;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @return int|\MongoId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|\MongoId $id
     *
     * @return ServerModule
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return BaseServer
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param BaseServer $server
     *
     * @return ServerModule
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return Module
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param Module $module
     *
     * @return ServerModule
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     *
     * @return ServerModule
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }
}
