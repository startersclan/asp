<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * @copyright   2013, BF2Statistics.com
 * @license     GNU GPL v3
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

    public function __construct($IpAddress)
    {
        // Make sure IP is valid!
        if(!filter_var($IpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            throw new \InvalidArgumentException("IPv6 Address is invalid!");

        $this->ipAddress = $IpAddress;
        $this->fullIpAddress = IPAddress::ExpandIPv6Notation($IpAddress, true);
        $this->isLocal = (
            $this->fullIpAddress == "0000:0000:0000:0000:0000:0000:0000:0001" || // IPv6 LocalLoopBack
            $this->fullIpAddress == "0000:0000:0000:0000:0000:ffff:7f00:0001" || // 127.0.0.1 converted to IPv6
            !filter_var($IpAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE)
        );
    }

    public function isLoopback()
    {
        return $this->isLocal;
    }

    public function equals($Ip)
    {
        if($Ip instanceof IPv6Address)
            return $Ip->equals($this->fullIpAddress);
        else
            return ($this->fullIpAddress == IPAddress::ExpandIPv6Notation($Ip));
    }

    public function getType()
    {
        return IPAddress::IP_VERSION_6;
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