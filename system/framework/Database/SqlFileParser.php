<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System\Database;


use System\IO\File;

class SqlFileParser
{
    private $i;
    private $end;
    private $sql;

    public function __construct($filePath)
    {
        // Get SQl statements
        $query = File::ReadAllText($filePath);

        // Prepare query for comment extraction!
        $this->end = strlen($query) - 1;
        $this->sql = $query;

        // Remove comments
        $this->sql = $this->removeComments();
        $this->end = strlen($this->sql) - 1;
    }

    public function getStatements()
    {
        $sqlList = array();

        $query = "";
        $delimiter = ';';
        $this->i = 0;

        // Parsing the SQL file content
        while (!$this->eof())
        {
            // Do not parse quoted strings
            if ($this->peek() === "'" || $this->peek() === "\"")
            {
                $term = $this->peek();
                $query .= $this->take();

                // Skip until we hit our quote term
                while ($this->peek() !== $term && !$this->eof())
                {
                    // Qoute term escaped? If so take twice and keep skipping
                    if ($this->peek() === "\\" && $this->peek(1) === $term)
                        $query .= $this->take();

                    $query .= $this->take();
                }

                // Append character to query builder
                $query .= $this->take();
                continue;
            }

            // Check for delimiter change
            if ($this->takeIfNext('delimiter', true))
            {
                // Take next whitespace
                $this->takeWhiteSpace();

                $delimiter = '';
                while (!$this->eof() && !$this->nextIsWhiteSpace())
                    $delimiter .= $this->take();

                // Take whitespace to next statement
                $this->takeWhiteSpace();
                continue;
            }

            // Check for end of statement
            if ($this->takeIfNext($delimiter))
            {
                // Store this query
                $sqlList[] = trim($query);

                // Reset the variable
                $query = "";

                // Remove whitespace from the start of next query
                $this->takeWhiteSpace();
                continue;
            }

            // Append character to query builder
            $query .= $this->take();
        }

        return $sqlList;
    }

    protected function removeComments()
    {
        $this->i = 0;
        $clean = "";

        while (!$this->eof())
        {
            if ($this->peek() === "'" || $this->peek() === "\"")
            {
                $term = $this->peek();
                $clean .= $this->take();

                while ($this->peek() !== $term && !$this->eof())
                {
                    if ($this->peek() === "\\" && $this->peek(1) === $term)
                        $clean .= $this->take();

                    $clean .= $this->take();
                }

                $clean .= $this->take();
                continue;
            }

            if ($this->takeIfNext("--") || $this->takeIfNext("#"))
            {
                while ($this->peek() !== "\n" && !$this->eof())
                    $this->take();

                $clean .= $this->take();
                continue;
            }

            if ($this->takeIfNext("/*"))
            {
                $nest = 1;
                while ($nest > 0 && !$this->eof())
                {
                    if ($this->takeIfNext("*/"))
                    {
                        --$nest;
                        continue;
                    }

                    if ($this->takeIfNext("/*"))
                    {
                        ++$nest;
                        continue;
                    }

                    $this->take();
                }
            }

            $clean .= $this->take();
        }

        if (strrpos($clean, ";") === strlen($clean) - 1)
            $clean = substr($clean, 0, strlen($clean) - 1);

        return $clean;
    }

    private function eof()
    {
        return $this->i >= $this->end;
    }

    private function isNext($search, $caseSensitive = true)
    {
        $len = strlen($search);
        if ($this->i + $len >= $this->end)
            return false;

        $compare = substr($this->sql, $this->i, $len);
        return ($caseSensitive)
            ? $search === $compare
            : strtolower($search) == strtolower($compare);
    }

    private function takeIfNext($search, $caseSensitive = true)
    {
        if ($this->isNext($search, $caseSensitive))
        {
            $this->i += strlen($search);

            return true;
        }

        return false;
    }

    private function peek($n = 0)
    {
        $newIndex = $this->i + $n;
        if ($newIndex >= $this->end)
            return "";

        return $this->sql[$newIndex];
    }

    private function take()
    {
        if ($this->eof())
            return "";

        $c = $this->peek();
        $this->i += 1;

        return $c;
    }

    public function takeWhiteSpace()
    {
        // Skip next whitespace characters from query
        while (!$this->eof() && $this->nextIsWhiteSpace())
            $this->take();
    }

    public function nextIsWhiteSpace()
    {
        return trim($this->peek()) == '';
    }

    /**
     * Determines whether the beginning of a string matches a specified string
     *
     * @param string $string The haystack
     * @param string $sub The needle
     * @param bool $caseSensitive true if case sensitive search, false otherwise
     *
     * @return bool
     */
    private function startsWith($string, $sub, $caseSensitive = true)
    {
        return ($caseSensitive)
            ? substr_compare($string, $sub, 0, strlen($sub)) === 0
            : substr_compare(strtolower($string), strtolower($sub), 0, strlen($sub)) === 0;
    }

    /**
     * Determines whether the end of a string matches the specified string
     *
     * @param string $string The haystack
     * @param string $sub The needle
     * @param bool $caseSensitive true if case sensitive search, false otherwise
     *
     * @return bool
     */
    private function endsWith($string, $sub, $caseSensitive = true)
    {
        $len = strlen($sub);
        return ($caseSensitive)
            ? substr_compare($string, $sub, -$len, $len) === 0
            : substr_compare(strtolower($string), strtolower($sub), -$len, $len) === 0;
    }
}