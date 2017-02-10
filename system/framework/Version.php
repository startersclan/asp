<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * @copyright   2013, BF2Statistics.com
 * @license     GNU GPL v3
 */
namespace System;


class Version
{
    public $major = 0;

    public $minor = 0;

    public $revision = 0;

    protected $intVal = 0;

    public function __construct($major, $minor = 0, $revision = 0)
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->revision = $revision;
    }

    public static function Parse($version)
    {
        if (!preg_match("/[0-9.]+/i", $version))
            throw new \InvalidArgumentException("Version string contains illegal characters");

        $ver_arr = explode(".", $version);
        $size = sizeof($ver_arr);
        if ($size >= 3)
        {
            $major = (int)$ver_arr[0];
            $minor = (int)$ver_arr[1];
            $revis = (int)$ver_arr[2];

            return new Version($major, $minor, $revis);
        }
        elseif ($size == 2)
        {
            $major = (int)$ver_arr[0];
            $minor = (int)$ver_arr[1];

            return new Version($major, $minor, 0);
        }
        else
            return new Version(intval($ver_arr[0]));
    }

    /**
     * Compares 2 versions and returns which is greater
     *
     * @param string|Version $version The Version to compare to
     *
     * @return int Returns 0 if the versions are equal, -1 if the $version
     *   variable is larger then this object version, or 1 if this version
     *   object is greater then $version
     */
    public function compare($version)
    {
        if (!($version instanceof Version))
            $version = Version::Parse($version);

        if ($version->toInt() == $this->toInt())
            return 0;
        elseif ($version->toInt() > $this->toInt())
            return -1;
        else
            return 1;
    }

    public function toInt()
    {
        if ($this->intVal == 0)
        {
            $this->intVal = $this->major * 10000;
            $this->intVal += $this->minor * 100;
            $this->intVal += $this->revision;
        }

        return $this->intVal;
    }
}