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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class BaseServer
{
    /**
     * @var int|\MongoId
     */
    protected $id;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $owner;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var Module[]|ArrayCollection
     */
    protected $modules;

    /**
     * AbstractIgnored constructor.
     */
    public function __construct()
    {
        $this->modules = new ArrayCollection();
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
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return BaseServer
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     *
     * @return BaseServer
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     *
     * @return BaseServer
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return Module[]|ArrayCollection
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param Module[]|ArrayCollection $modules
     *
     * @return BaseServer
     */
    public function setModules(array $modules)
    {
        $this->modules = new ArrayCollection($modules);

        return $this;
    }
}
