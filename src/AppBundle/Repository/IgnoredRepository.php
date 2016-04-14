<?php
/**
 * This file is part of discord-base-bot
 *
 * Copyright (c) 2016 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

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

use Discord\Base\AppBundle\Model\Ignored;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class IgnoredRepository
{
    /**
     * @var array|ArrayCollection|Ignored[]
     */
    private $ignores;

    /**
     * BotCommandRepository constructor.
     *
     * @param array|ArrayCollection|Ignored[] $ignores
     */
    public function __construct(array $ignores = [])
    {
        $this->ignores = new ArrayCollection($ignores);
    }

    /**
     * @return array|ArrayCollection|Ignored[]
     */
    public function all()
    {
        return $this->ignores;
    }

    /**
     * @param Ignored $ignored
     *
     * @return IgnoredRepository
     */
    public function add(Ignored $ignored)
    {
        if (!$this->ignores->contains($ignored)) {
            $this->ignores->add($ignored);
        }

        return $this;
    }

    /**
     * @param Ignored $ignored
     *
     * @return IgnoredRepository
     */
    public function replace(Ignored $ignored)
    {
        foreach ($this->ignores as $key => $value) {
            if ($value->getId() === $ignored->getId()) {
                $this->ignores->set($key, $ignored);

                return $this;
            }
        }

        return $this;
    }

    public function findOneBy(array $properties)
    {
        foreach ($this->ignores as $ignore) {
            if (isset($properties['type']) && $properties['type'] !== $ignore->getType()) {
                continue;
            }
            if (isset($properties['identifier']) && $properties['identifier'] !== $ignore->getIdentifier()) {
                continue;
            }
            if (isset($properties['ignored']) && $properties['ignored'] !== $ignore->getIgnored()) {
                continue;
            }

            return $ignore;
        }
    }
}
