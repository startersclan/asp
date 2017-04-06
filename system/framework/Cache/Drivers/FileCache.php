<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System\Cache\Drivers;

use System\Cache\CacheItem;
use System\Cache\ICacheDriver;
use System\Collections\Dictionary;
use System\IO\File;
use System\IO\FileStream;
use System\IO\Path;

class FileCache implements ICacheDriver
{
    protected $dir = SYSTEM_PATH . DS . "cache";

    protected $extension = "cache";

    protected static $Cache;

    /**
     * FileCache constructor.
     */
    public function __construct()
    {
        if (is_null(self::$Cache))
            self::$Cache = new Dictionary(false);
    }

    /**
     * Gets an item from the cache with the specified name, or a
     * new item if the cached item did not exist ot was expired
     *
     * @param string $name
     *
     * @return CacheItem
     */
    public function getItem($name)
    {
        // Check if the item is cached
        if (self::$Cache->containsKey($name))
            return self::$Cache[$name];

        // Create return item
        $item = new CacheItem($name, $this);

        // Create the full file name
        $fileName = Path::Combine($this->dir, $item->getEncodedKey() . '.' . $this->extension);

        // Check if cache file exists
        if (File::Exists($fileName))
        {
            // Read the contents from the file
            $file = new FileStream($fileName, FileStream::READ);
            $data = json_decode($file->readToEnd(), true);
            $file->close();

            // Ensure this is a timestamp!
            if (is_int($data['expires']))
            {
                try
                {
                    // Check if item is expired first
                    $data = new Dictionary(false, $data);
                    $expires = new \DateTime("@{$data['expires']}");
                    $item->expiresAt($expires);

                    // If item is expired, reset value
                    if ($item->isExpired())
                    {
                        $item->set(null);
                        $item->expiresAfter(new \DateInterval("P7D"));
                    }
                    else
                    {
                        $item->set($data['data']);
                    }
                }
                catch (\Exception $e)
                {
                    // Ignore - bad cache file format
                }
            }
        }

        // Add to cache
        self::$Cache->add($name, $item);
        return $item;
    }

    /**
     * Saves an item to the cache
     *
     * @param CacheItem $item
     *
     * @return void
     */
    public function save(CacheItem $item)
    {
        $data = [
            'key' => $item->getKey(),
            'expires' => $item->getExpirationDate()->getTimestamp(),
            'data' => $item->get()
        ];

        // Create the full file name and contents array
        $fileName = Path::Combine($this->dir, $item->getEncodedKey() . '.' . $this->extension);
        $contents = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

        // Save contents
        $file = new FileStream($fileName, 'w+');
        $file->write($contents);
        $file->close();

        // Save cache
        self::$Cache->add($item->getKey(), $item);
    }

    /**
     * Indicates whether the item key is cached
     *
     * @param string $key The cached item key
     *
     * @return bool
     */
    public function hasItem($key)
    {
        $item = new CacheItem($key, $this);
        $fileName = Path::Combine($this->dir, $item->getEncodedKey() . '.' . $this->extension);
        return File::Exists($fileName);
    }

    /**
     * Deletes the cached item key is it exists
     *
     * @param string $key The cached item key
     *
     * @return void
     */
    public function deleteItem($key)
    {
        $item = new CacheItem($key, $this);
        $fileName = Path::Combine($this->dir, $item->getEncodedKey() . '.' . $this->extension);
        File::Delete($fileName);
    }
}