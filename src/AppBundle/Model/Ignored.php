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
class Ignored
{
    /**
     * @var int|\MongoId
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $ignored = true;

    /**
     * AbstractIgnored constructor.
     */
    public function __construct()
    {
        $this->ignored = true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return AbstractIgnored
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return AbstractIgnored
     */
    public function setType($type)
    {
        $this->type = $type;

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
     * @return AbstractIgnored
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getIgnored()
    {
        return $this->ignored;
    }

    /**
     * @param string $ignored
     *
     * @return AbstractIgnored
     */
    public function setIgnored($ignored)
    {
        $this->ignored = $ignored;

        return $this;
    }
}
