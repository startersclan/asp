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

class IPv6Address implements iIPAddress
{
    /**
     * The original IP address that was passed
     * @var string
     */
    protected $ipAddress = "";

    /**
     * Full expanded IP address
     * @var string
     */
    protected $fullIpAddress = "";

    /**
     * Indicates whether the IP address is local
     * @var bool
     */
    protected $isLocal;

    /**
     * IPv6Address constructor.
     *
     * @param string $address An IPv6 address.
     */
    public function __construct($address)
    {
        // Make sure IP is valid!
        if (!filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            throw new \InvalidArgumentException("IPv6 Address is invalid!");

        $flags = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        $this->ipAddress = $address;
        $this->fullIpAddress = IPAddress::ExpandIPv6Notation($address, true);
        $this->isLocal = !filter_var($address, FILTER_VALIDATE_IP, $flags);
    }

    /**
     * Indicates whether the specified IP address is the loopback address.
     *
     * @return bool
     */
    public function isLoopback()
    {
        return $this->isLocal;
    }

    /**
     * Compares the current IPAddress instance with the comparing parameter and returns true
     *  if the two instances contain the same IP address.
     *
     * @param string|iIPAddress $Ip The IP address to compare with
     *
     * @return bool
     */
    public function equals($Ip)
    {
        if ($Ip instanceof IPv6Address)
            return $Ip->equals($this->fullIpAddress);
        else
            return ($this->fullIpAddress == IPAddress::ExpandIPv6Notation($Ip));
    }

    /**
     * Returns the IP address type (@see IPAddress::IP_VERSION_*)
     *
     * @return int
     */
    public function getType()
    {
        return IPAddress::IP_VERSION_6;
    }

    /**
     * Returns the IPv6 colon-hexadecimal notation.
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