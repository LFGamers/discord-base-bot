<?php

/*
 * This file is part of discord-servers-bot.
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */

namespace Discord\Base\AppBundle\Twig;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 *
 * AppExtension Class
 */
class AppExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('pad_longest', [$this, 'padLongest']),
        ];
    }

    /**
     * @param string $value
     * @param array  $items
     * @param null   $attribute
     *
     * @return string
     */
    public function padLongest($value, array $items, $attribute = null)
    {
        $longest = 0;
        foreach ($items as $item) {
            if (isset($attribute)) {
                if (is_array($item)) {
                    if (isset($item[$attribute])) {
                        $val = $item[$attribute];
                    } else {
                        throw new \InvalidArgumentException("Property with that value does not exist.");
                    }
                } else {
                    $attr = str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute)));
                    if (method_exists($item, 'get' . $attr)) {
                        $val = $item->{'get'.$attr}();
                    } else if (method_exists($item, 'is' . $attr)) {
                        $val = $item->{'is'.$attr}();
                    } else {
                        throw new \InvalidArgumentException("Property with that value does not exist.");
                    }
                }
            } else {
                $val = $item;
            }

            $longest = strlen($val) > $longest ? strlen($val) : $longest;
        }

        return str_pad($value, $longest, ' ', STR_PAD_RIGHT);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'app';
    }
}
