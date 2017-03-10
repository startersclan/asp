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

class Navigation
{
    /**
     * @var NavigationItem[]
     */
    protected $items = [];

    /**
     * Appends a Navigation Item to the current navigation set.
     *
     * @param NavigationItem $item
     */
    public function append(NavigationItem $item)
    {
        $this->items[] = $item;
    }

    /**
     * Generates the un-ordered navigation list
     *
     * @return string
     */
    public function toHtml()
    {
        $html = '<ul>';

        foreach ($this->items as $item)
        {
            $html .= "\n";
            $html .= $item->toHtml();
        }

        $html .= "\n";
        $html .= '</ul>';

        return $html;
    }
}