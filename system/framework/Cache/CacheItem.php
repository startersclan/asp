<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2021, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System\Cache;

/**
 * Class CacheItem
 * @package System\Cache
 */
class CacheItem
{
    /**
     * @var bool
     */
    protected $fetched = false;

    /**
     * @var ICacheDriver
     */
    protected $driver;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var \DateTime
     */
    protected $expirationDate;

    /**
     * CacheItem constructor.
     *
     * @param string $key The name of this cached item
     * @param ICacheDriver $driver The driver used to cache this item
     */
    public function __construct($key, ICacheDriver $driver)
    {
        $this->key = $key;
        $this->driver = $driver;
    }

    /**
     * @param \DateInterval|int $time
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function expiresAfter($time)
    {
        if (is_numeric($time))
        {
            if ($time <= 0)
            {
                /**
                 * 5 years, however memcached or memory cached will gone when u restart it
                 * just recommended for sqlite. files
                 */
                $time = 30 * 24 * 3600 * 5;
            }
            $this->expirationDate = (new \DateTime())->add(new \DateInterval(sprintf('PT%dS', $time)));
        }
        else if ($time instanceof \DateInterval)
        {
            $this->expirationDate = (new \DateTime())->add($time);
        }
        else
        {
            throw new \InvalidArgumentException('Invalid date format');
        }

        return $this;
    }

    /**
     * @param \DateTimeInterface $expiration
     *
     * @return $this
     */
    public function expiresAt($expiration)
    {
        if ($expiration instanceof \DateTimeInterface)
        {
            $this->expirationDate = $expiration;
        }
        else
        {
            throw new \InvalidArgumentException('$expiration must be an object implementing the DateTimeInterface');
        }

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function set($value)
    {
        /**
         * The user set a value,
         * therefore there is no need to
         * fetch from source anymore
         */
        $this->fetched = true;
        $this->data = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getEncodedKey()
    {
        return md5($this->getKey());
    }

    /**
     * @return \DateTimeInterface
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return $this->expirationDate->getTimestamp() < (new \DateTime())->getTimestamp();
    }

    /**
     * @param int $step
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function increment($step = 1)
    {
        if (is_int($step))
        {
            $this->fetched = true;
            $this->data += $step;
        }
        else
        {
            throw new \InvalidArgumentException('$step must be numeric.');
        }

        return $this;
    }

    /**
     * @param int $step
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function decrement($step = 1)
    {
        if (is_int($step))
        {
            $this->fetched = true;
            $this->data -= $step;
        }
        else
        {
            throw new \InvalidArgumentException('$step must be numeric.');
        }

        return $this;
    }

    /**
     * @param array|string $data
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function append($data)
    {
        if (is_array($this->data))
        {
            array_push($this->data, $data);
        }
        else if (is_string($data))
        {
            $this->data .= (string)$data;
        }
        else
        {
            throw new \InvalidArgumentException('$data must be either array nor string.');
        }

        return $this;
    }

    /**
     * @param array|string $data
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function prepend($data)
    {
        if (is_array($this->data))
        {
            array_unshift($this->data, $data);
        }
        else if (is_string($data))
        {
            $this->data = (string)$data . $this->data;
        }
        else
        {
            throw new \InvalidArgumentException('$data must be either array nor string.');
        }

        return $this;
    }

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     *
     * @param int $option json_encode() options
     * @param int $depth json_encode() depth
     *
     * @return string
     */
    public function getDataAsJsonString($option = 0, $depth = 512)
    {
        $data = $this->get();
        if (is_object($data) || is_array($data))
        {
            $data = json_encode($data, $option, $depth);
        }
        else
        {
            $data = json_encode([$data], $option, $depth);
        }

        return json_encode($data, $option, $depth);
    }

    /**
     * Saves this item to the cache
     */
    public function save()
    {
        $this->driver->save($this);
    }
}