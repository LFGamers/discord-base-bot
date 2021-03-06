<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Factory;

use Discord\Base\Request;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Monolog\Logger;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * ServerManagerFactory Class
 */
class RequestFactory
{
    /**
     * @var Discord
     */
    protected $discord;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var int
     */
    protected $adminId;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var bool
     */
    protected $interactive;

    /**
     * ServerManagerFactory constructor.
     *
     * @param Discord           $discord
     * @param Logger            $logger
     * @param \Twig_Environment $twig
     * @param int               $adminId
     * @param string            $prefix
     * @param bool              $interactive
     */
    public function __construct(
        Discord $discord,
        Logger $logger,
        \Twig_Environment $twig,
        $adminId,
        $prefix,
        $interactive
    ) {
        $this->discord     = $discord;
        $this->logger      = $logger;
        $this->twig        = $twig;
        $this->adminId     = $adminId;
        $this->prefix      = $prefix;
        $this->interactive = $interactive;
    }

    /**
     * @param Message $message
     *
     * @return Request
     */
    public function create(Message $message)
    {
        $request = new Request($this->discord, $this->logger, $this->twig, $this->adminId, $this->prefix, $message);
        if (!$this->interactive) {
            $request->setInteractive(false);
        }

        return $request;
    }
}
