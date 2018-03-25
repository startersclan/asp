<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System\Net;

/**
 * Provides an Internet Protocol (IP) address.
 *
 * @package System\Net
 */
class IPv4Address implements iIPAddress
{
    /**
     * @var string The ip address string
     */
    protected $ipAddress = "";

    /**
     * @var bool Indicates whether this is a local IP address
     */
    protected $isLocal;

    /**
     * IPv4Address constructor.
     *
     * @param string $address An IPv4 address.
     */
    public function __construct($address)
    {
        // Make sure IP is valid!
        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            throw new \InvalidArgumentException("IPv4 Address is invalid!");

        // Define local properties
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        $this->ipAddress = $address;
        $this->isLocal = (substr($address, 0, 4) == "127." || !filter_var($address, FILTER_VALIDATE_IP, $flags));
    }

    /**
     * Returns whether this IP address is the loopback address (Localhost)
     *
     * @return bool
     */
    public function isLoopback()
    {
        return $this->isLocal;
    }

    /**
     * Returns whether this IP Address is equal to the supplied IP
     *
     * @param string|iIPAddress $Ip The IPAddress to compare to
     *
     * @return bool
     */
    public function equals($Ip)
    {
        if ($Ip instanceof IPv4Address)
            return ($Ip->toString() == $this->ipAddress);
        else
            return ($this->ipAddress == $Ip);
    }

    /**
     * Returns the IP address type (@see IPAddress::IP_VERSION_*)
     *
     * @return int
     */
    public function getType()
    {
        return IPAddress::IP_VERSION_4;
    }

    /**
     * Returns the IPv4 dotted-quad notation.
     *
     * @return string
     */
    public function toString()
    {
        return $this->ipAddress;
    }

    public function __toString()
    {
        return $this->ipAddress;
    }

}