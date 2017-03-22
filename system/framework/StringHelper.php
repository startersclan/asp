<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;

class StringHelper
{
    /**
     * Cuts a string to the specified length, while maintaining full words.
     *
     * @param string $text The string we are cutting the length of
     * @param int $maxLength The max length of the string to return
     * @param string $suffix The suffix to add to the end of the string if the string
     *  is too long.
     *
     * @return string
     */
    public static function SubStrWords($text, $maxLength, $suffix = '...')
    {
        if (strlen($text) > $maxLength)
        {
            // Convert text into an array of words
            $words = preg_split('/\s/', $text);

            // Create buffer, and set length of the suffix
            $output = '';
            $suffixLen = strlen($suffix);
            $i = 0;

            while (true)
            {
                // Calculate length when adding the next word.
                // Add suffix length, as well as +1 for the space
                $length = strlen($output) + strlen($words[$i]) + $suffixLen + 1;
                if ($length > $maxLength)
                    break;

                $output .= " " . $words[$i];
                ++$i;
            }

            $output .= $suffix;
        }
        else
        {
            $output = $text;
        }

        return $output;
    }

    /**
     * Determines whether the end of a string matches the specified string
     */
    public static function StringEndsWith( $string, $sub )
    {
        $len = strlen( $sub );
        return substr_compare( $string, $sub, -$len, $len ) === 0;
    }

    /**
     * Determines whether the beginning of a string matches a specified string
     */
    public static function StringStartsWith( $string, $sub )
    {
        return substr_compare( $string, $sub, 0, strlen( $sub ) ) === 0;
    }
}