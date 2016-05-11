<?php

/*
 * This file is part of discord-base-bot
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Repository;

use Discord\Base\AppBundle\Manager\ServerManager;
use Illuminate\Support\Collection;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * ServerManagerRepository Class
 */
class ServerManagerRepository extends Collection
{
    /**
     * @var ServerManager[]
     */
    protected $items;

    /**
     * @param int  $id
     * @param null $default
     *
     * @return ServerManager|null
     */
    public function get($id, $default = null)
    {
        foreach ($this->items as $item) {
            if ($item->getClientServer()->id === $id) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function has($id)
    {
        foreach ($this->items as $item) {
            if ($item->getClientServer()->id === $id) {
                return true;
            }
        }

        return false;
    }
}
