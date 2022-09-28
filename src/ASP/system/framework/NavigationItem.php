<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2021, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System;

/**
 * Class NavigationItem
 * @package System
 */
class NavigationItem
{
    /**
     * @var string Gets or Sets the text for this navigation group
     */
    public $text = '';

    /**
     * @var string Gets or Sets the navigation href
     */
    public $href = '#';

    /**
     * @var bool Indicates whether this navigation menu is open or closed.
     */
    public  $isActive = false;

    /**
     * @var string Gets or Sets the icon for this navigation menu
     */
    public $icon = '';

    /**
     * @var array Contains the sub menu links
     */
    public $sublinks = [];

    /**
     * NavigationItem constructor.
     *
     * @param string $text
     * @param string $href
     * @param string $icon
     * @param bool $isActive
     */
    public function __construct($text, $href, $icon, $isActive)
    {
        $this->text = $text;
        $this->href = $href;
        $this->isActive = $isActive;
        $this->icon = $icon;
    }

    /**
     * Appends a sub link in this navigation group
     *
     * @param string $href The location this link points to
     * @param string $text The text for the link
     */
    public function append($href, $text)
    {
        $this->sublinks[$href] = $text;
    }

    /**
     * Returns the HTML representation of this NavigationItem
     *
     * @return string
     */
    public function toHtml()
    {
        $html = "\t<li";
        $html .= (($this->isActive) ? ' class="active">' : '>');
        $html .= "\n\t\t";

        // Add link
        $html .= '<a href="'. $this->href .'"><i class="'. $this->icon .'"></i> '. $this->text .'</a>';

        if (!empty($this->sublinks))
        {
            $html .= "\n\t\t\t<ul";
            $html .= ((!$this->isActive) ? ' class="closed">' : '>');

            foreach ($this->sublinks as $href => $text)
            {
                $html .= "\n\t\t\t\t";
                $html .= '<li><a href="'. $href .'">'. $text .'</a></li>';
            }

            $html .= "\n\t\t\t</ul>";
        }

        $html .= "\n\t</li>";
        return $html;
    }
}