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
class Module
{
    /**
     * @var int|\MongoId
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $defaultEnabled = false;

    /**
     * AbstractIgnored constructor.
     */
    public function __construct()
    {
        $this->defaultEnabled = false;
    }

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
     * @return Module
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Module
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultEnabled()
    {
        return $this->defaultEnabled;
    }

    /**
     * @param string $defaultEnabled
     *
     * @return Module
     */
    public function setDefaultEnabled($defaultEnabled)
    {
        $this->defaultEnabled = $defaultEnabled;

        return $this;
    }
}
