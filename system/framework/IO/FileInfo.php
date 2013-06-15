<?php
/**
 * Plexis Content Management Asp
 *
 * @file        system/framework/IO/FileInfo.php
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\IO;
use FileNotFoundException;
use IOException;
use Exception;

/**
 * Provides properties and instance methods for various file operations
 *
 * Use the FileInfo class if you are going to reuse an object several times,
 * because a file exists check will not always be necessary, and will increase
 * performance over the static File class methods
 *
 * @author      Steven Wilson 
 * @package     Asp
 * @subpackage  IO
 */
class FileInfo
{
    /**
     * The full path to the file's parent directory
     * @var string
     */
    protected $parentDir;

    /**
     * The full path to the file's current location, including the filename
     * @var string
     */
    protected $filePath;

    /**
     * Class Constructor
     *
     * @param string $path The full path the the file
     * @param bool $create Create the file if it doesn't exist?
     *
     * @throws \IOException Thrown if the $path directory doesn't exist,
     *   $create is set to true, and there was an error creating the file.
     * @throws \FileNotFoundException If the $path file does not exist, and $create is set to false.
     * @throws \Exception Thrown if the $path is not a file at all, but rather a directory
     */
    public function __construct($path, $create = false)
    {
        // Make sure the file exists, or we are creating a file
        if(!file_exists($path))
        {
            // Do we attempt to create?
            if(!$create)
                throw new FileNotFoundException("File '{$path}' does not exist");

            // Attempt to create the file
            $handle = @fopen($path, 'w+');
            if($handle)
            {
                // Close the handle
                fclose($handle);
            }
            else
                throw new IOException("Cannot create file '{$path}'");
        }
        elseif(!is_file($path))
        {
            throw new Exception("'{$path}' is not a file!");
        }

        // Define path
        $this->filePath = $path;
        $this->parentDir = dirname($path);
    }

    /**
     * Returns the base file name
     *
     * @return string
     */
    public function name()
    {
        return basename($this->filePath);
    }

    /**
     * Returns the full path to the file, including the file name
     *
     * @return string
     */
    public function fullName()
    {
        return $this->filePath;
    }

    /**
     * Returns the the extension part of the file
     *
     * @return string
     */
    public function extension()
    {
        return pathinfo($this->filePath, PATHINFO_EXTENSION);
    }

    /**
     * Returns the files 's directory path
     *
     * @return string
     */
    public function directoryName()
    {
        return $this->parentDir;
    }

    /**
     * Gets an instance of the parent directory
     *
     * @return DirectoryInfo
     */
    public function directory()
    {
        return new DirectoryInfo($this->parentDir);
    }

    /**
     * Appends the specified string to the file
     *
     * @param string $stringData The data string to write to the file
     *
     * @throws IOException Thrown if there was an error opening, or writing to the file.
     *
     * @return bool Returns whether the operation was successful
     */
    public function appendText($stringData)
    {
        $File = new FileStream($this->filePath, FileStream::WRITE);
        $wrote = $File->write($stringData);
        $File->close();
        return $wrote !== false;
    }

    /**
     * Opens a FileStream on the specified path with read/write access
     *
     * @throws IOException Thrown if there was an error opening the file.
     *
     * @return \System\IO\FileStream
     */
    public function open()
    {
        return new FileStream($this->filePath, FileStream::READWRITE);
    }

    /**
     * Opens a FileStream on the specified path with read access
     *
     * @throws IOException Thrown if there was an error opening the file.
     *
     * @return \System\IO\FileStream
     */
    public function openRead()
    {
        return new FileStream($this->filePath, FileStream::READ);
    }

    /**
     * Opens a FileStream on the specified path with write access
     *
     * @throws IOException Thrown if there was an error opening the file.
     *
     * @return \System\IO\FileStream
     */
    public function openWrite()
    {
        return new FileStream($this->filePath, FileStream::WRITE);
    }

    /**
     * Moves the file to a new location.
     *
     * The old file will not be removed until the new file is created successfully.
     *
     * @param string $newPath The full file name (including full path) to move to
     *
     * @throws IOException Thrown if there was an error creating the new directory path
     * @throws \SecurityException Thrown if the $newPath directory could not be opened
     *   for various security reasons such as permissions.
     *
     * @return void
     */
    public function moveTo($newPath) 
    {
        $result = @copy($this->filePath, $newPath);
        if(!$result)
        {
            $error = error_get_last();
            if($error === null)
                throw new IOException("Cannot copy file \"{$this->filePath}\" to \"{$newPath}\".");
            else
                throw new IOException($error["message"]);
        }

        // Delete old file
        @unlink($this->filePath);

        // Reset class vars
        $this->filePath = $newPath;
        $this->parentDir = dirname($newPath);
    }

    /**
     * Copies the contents of this file to a new file
     *
     * @param string $fileName The name of the file we are copying to
     * @param bool $overwrite Defines whether to overwrite an existing
     * 	 file, if it exists
     *
     * @return bool Returns true on success, false otherwise
     */
    public function copyTo($fileName, $overwrite = false) 
    {
        // If file exists, and we disallow overwriting
        if(!$overwrite && file_exists($fileName))
            return false;

        // return the copy result
        return @copy($this->filePath, $fileName);
    }

    /**
     * Completely removes all contents of the file
     *
     * @return bool Returns true on success, false otherwise
     */
    public function truncate() 
    {
        $f = @fopen($this->filePath, "r+");
        if ($f !== false) 
        {
            ftruncate($f, 0);
            fclose($f);
            return true;
        }
        return false;
    }

    /**
     * Gets last modification time of file
     *
     * @return int|bool Returns the time the file was last modified, 
     * or FALSE on failure. The time is returned as a Unix timestamp.
     */
    public function lastWriteTime() 
    {
        return filemtime($this->filePath);
    }

    /**
     * Gets last access time of file
     *
     * @return int|bool Returns the time the file was last accessed, 
     * or FALSE on failure. The time is returned as a Unix timestamp.
     */
    public function lastAccessTime() 
    {
        return fileatime($this->filePath);
    }

    /**
     * Gets the size,  in bytes, of the current file
     *
     * @param bool $format Format the file size to human readable format?
     * @param bool $gt2gb Do we think this file to be over 2 GB? This is used to get
     *   an accurate file size via the command line on 32 bit systems.
     *
     * @return float|string|bool Returns false on failure, a float if $format is false, or
     *   a string if $format is true
     */
    public function size($format = false, $gt2gb = false) 
    {
        // Get most accurate file size based on operating system
        $total_size = '0';
        $is64Bit = (PHP_INT_MAX > 2147483647);

        // If we suspect the file being over 2 GB on a 32 bit system, use command line
        if($gt2gb && !$is64Bit)
        {
            // Get file size
            $isWindows = (substr(strtoupper(PHP_OS), 0, 3) === 'WIN');
            $total_size = ($isWindows)
                ? exec("for %v in (\"". $this->filePath ."\") do @echo %~zv") // Windows
                : shell_exec("stat -c%s " . escapeshellarg($this->filePath)); // Linux

            // If we failed to get a size, we take extreme measures
            if(!$total_size || !is_numeric($total_size))
            {
                if($isWindows)
                {
                    // Check for windows COM
                    if(class_exists("COM", false)) 
                    {
                        /** @noinspection PhpUndefinedClassInspection */
                        $fsobj = new \COM('Scripting.FileSystemObject');

                        /** @noinspection PhpUndefinedMethodInspection */
                        $f = $fsobj->GetFile($this->filePath);
                        $total_size = (float) $f->Size;
                    }
                    else 
                    {
                        return false;
                    }
                }
                else
                {
                    $total_size = trim(exec("perl -e 'printf \"%d\n\",(stat(shift))[7];' ". $this->filePath));
                    if(!$total_size || !is_numeric($total_size))
                        return false;
                }
            }
        }

        // Just to make sure, try and return the filesize() if nothing else
        if($total_size == '0' || !$total_size || !is_numeric($total_size)) 
            $total_size = (float) @filesize($this->filePath);
        return ($format == true) ? $this->formatSize($total_size) : (float) $total_size;
    }

    /**
     * Fetches or sets the permissions of the file
     *
     * @param int $ch The permission (octal) chmod level to set on the file.
     *   If left unset, the current chmod will be returned.
     *
     * @return int|bool Returns the current file chmod if $ch is left null,
     *   otherwise, returns the success value of setting the permissions.
     */
    public function chmod($ch = null) 
    {
        if(empty($ch))
            return fileperms($this->filePath);

        return chmod($this->filePath, $ch);
    }

    /**
     * Returns whether this file is writable or not.
     *
     * @return bool
     */
    public function isWritable() 
    {
        // Attempt to open the file, and read contents
        $handle = @fopen($this->filePath, 'a');
        if($handle === false) 
            return false;

        // Close the file, return true
        fclose($handle);
        return true;
    }

    /**
     * Returns whether this file is readable or not.
     *
     * @return bool
     */
    public function isReadable()
    {
        // Attempt to open the file, and read contents
        $handle = @fopen($this->filePath, 'r');
        if($handle === false)
            return false;

        // Close the file, return true
        fclose($handle);
        return true;
    }

    /**
     * Formats a file size to human readable format
     *
     * @param string|float|int The size in bytes
     *
     * @return string Returns a formatted size ( Ex: 32.6 MB )
     */
    protected function formatSize($size)
    {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');
        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
        return round($size, 2) . $units[$i];
    }

    /**
     * When used as a string, this object returns the full path to the file.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->filePath;
    }

    public function __destruct()
    {

    }
}