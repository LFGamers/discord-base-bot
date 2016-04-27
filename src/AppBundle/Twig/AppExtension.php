<?php

/*
 * This file is part of discord-base-bot
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
            new \Twig_SimpleFunction('truncate', [$this, 'truncate']),
        ];
    }

    /**
     * @param string $value
     * @param array  $items
     * @param string $attribute
     * @param int    $maxLength
     *
     * @return string
     */
    public function padLongest($value, array $items, $attribute = null, $maxLength = null)
    {
        $longest = 0;
        foreach ($items as $item) {
            if (isset($attribute)) {
                if (is_array($item)) {
                    if (isset($item[$attribute])) {
                        $val = $item[$attribute];
                    } else {
                        throw new \InvalidArgumentException('Property with that value does not exist.');
                    }
                } else {
                    $attr = str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute)));
                    if (method_exists($item, 'get'.$attr)) {
                        $val = $item->{'get'.$attr}();
                    } else {
                        if (method_exists($item, 'is'.$attr)) {
                            $val = $item->{'is'.$attr}();
                        } else {
                            throw new \InvalidArgumentException("Property with that value does not exist.");
                        }
                    }
                }
            } else {
                $val = $item;
            }

            if ($maxLength !== null) {
                $val = $this->truncate($val, $maxLength);
            }

            $longest = strlen($val) > $longest ? strlen($val) : $longest;
        }

        if ($maxLength !== null) {
            $value = $this->truncate($value, $maxLength);
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

    /**
     * Original PHP code by Chirp Internet: www.chirp.com.au
     * Please acknowledge use of this code by including this header.
     *
     * @param string $string
     * @param int    $limit
     * @param string $break
     * @param string $pad
     *
     * @return string
     */
    private function truncate($string, $limit, $break = " ", $pad = "...")
    {
        // return with no change if string is shorter than $limit
        if (strlen($string) <= $limit) {
            return $string;
        }

        // is $break present between $limit and the end of the string?
        if (false !== ($breakpoint = strpos($string, $break, $limit))) {
            if ($breakpoint < strlen($string) - 1) {
                $string = substr($string, 0, $breakpoint).$pad;
            }
        }

        if (strpos($string, $break) === false) {
            return substr($string, 0, $limit).$pad;
        }

        return $this->truncate(substr($string, 0, strrpos($string, $break)), $limit, $break, $pad).$pad;
    }
}
