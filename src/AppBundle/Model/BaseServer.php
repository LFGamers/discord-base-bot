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
     * @var ServerModule[]|ArrayCollection
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
     * @return BaseServer
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
     * @return ServerModule[]|ArrayCollection
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * @param ServerModule[]|ArrayCollection $modules
     *
     * @return BaseServer
     */
    public function setModules(array $modules)
    {
        $this->modules = new ArrayCollection($modules);

        return $this;
    }

    /**
     * @param ServerModule $module
     *
     * @return BaseServer
     */
    public function addModule(ServerModule $module)
    {
        if (!$this->hasModule($module->getModule())) {
            $this->modules->add($module);
        }

        return $this;
    }

    /**
     * @param Module $module
     *
     * @return bool
     */
    public function hasModule(Module $module)
    {
        foreach ($this->modules as $serverModule) {
            if ($serverModule->getModule()->getId() === $module->getId()) {
                return true;
            }
        }

        return false;
    }
}
