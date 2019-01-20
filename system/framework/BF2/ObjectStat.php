<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2019, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System\BF2;

/**
 * Class ObjectStat
 *
 * @package System\BF2
 */
class ObjectStat
{
    /**
     * @var int The object id
     */
    public $id = 0;

    /**
     * @var int The time in seconds played with this object
     */
    public $time = 0;

    /**
     * @var int The number of kills with this object
     */
    public $kills = 0;

    /**
     * @var int The number of deaths with this object
     */
    public $deaths = 0;

    /**
     * @var int The number of shots fired with this object
     */
    public $fired = 0;

    /**
     * @var int The number of hits with this object
     */
    public $hits = 0;

    /**
     * @var int The number of road kills with this object
     */
    public $roadKills = 0;

    /**
     * @var int The number of times this object was deployed
     */
    public $deployed = 0;
}