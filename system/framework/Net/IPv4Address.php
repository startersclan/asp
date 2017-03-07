<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System\Net;

class IPv4Address implements iIPAddress
{
    protected $ipAddress = "";
    protected $isLocal;

    public function __construct($IpAddress)
    {
        // Make sure IP is valid!
        if(!filter_var($IpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            throw new \InvalidArgumentException("IPv4 Address is invalid!");

        $this->ipAddress = $IpAddress;
        $this->isLocal = (substr($IpAddress, 0, 4) == "127." ||
            !filter_var($IpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)
        );
    }

    /**
     * Returns whether this IP address is the loopback address (Localhost)
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
        if($Ip instanceof IPv4Address)
            return ($Ip->toString() == $this->ipAddress);
        else
            return ($this->ipAddress == $Ip);
    }

    public function getType()
    {
        return IPAddress::IP_VERSION_4;
    }

    public function toString()
    {
        return $this->ipAddress;
    }

    public function __toString()
    {
        return $this->ipAddress;
    }

}