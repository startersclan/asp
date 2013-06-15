<?php
/**
 * Plexis Content Management Asp
 *
 * @file        system/framework/IO/DirectoryInfo.php
 * @copyright   2013 Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\IO;
use IOException;

/**
 * Provides properties and instance methods for various file stream operations
 *
 * Use the FileStream class to read from, write to, open, and close files on a file system
 *
 * @author      Steven Wilson 
 * @package     Asp
 * @subpackage  IO
 */
class FileStream
{
    // FileMode constant for Read + Write
    const READWRITE = "a+";

    // FileMode constant Read Only
    const READ = "r";

    // FileMode constant Write Only
    const WRITE = "a";

    /**
     * The file stream
     * @var Resource
     */
    protected $stream;

    /**
     * File mode variable
     * @var string
     */
    protected $mode;

    /**
     * Constructor
     *
     * @param string|resource $file The full path to the file, or the file resource
 *       (opened with {@link fopen}).
     *	 If the file does not exist, it will be created
     * @param string $mode The Read / Write mode of the file (See class Constants READ,
     *	 WRITE, READWRITE etc ).
     *
     * @throws IOException Thrown if opening of the file stream failed for any reason
     */
    public function __construct($file, $mode = self::READWRITE)
    {
        // Setup class vars
        $this->stream = (is_resource($file)) ? $file : @fopen($file, $mode);
        $this->mode = $mode;

        // Make sure our stream is valid
        if($this->stream === false)
        {
            $error = error_get_last();
            if($error === null)
                throw new IOException("Unable to open file stream for file \"{$file}\".");
            else
                throw new IOException($error["message"]);
        }

        // Set write buffer to 0 to prevent multiple streams on this file messing up
        stream_set_write_buffer($this->stream, 0);
    }

    /**
     * Reads data from file
     *
     * @param int $maxsize The maximum amount of bytes to read. To read all data, set to -1
     * @param int $offset The number of bytes to offset in the read operation
     * @return string Returns the string data in the file
     */
    public function read($maxsize = -1, $offset = 0)
    {
        // If we are at the end of the file, return false
        if(feof($this->stream))
            return false;

        // Get contents
        $contents = stream_get_contents($this->stream, $maxsize, $offset);

        // Sometimes stream_get_contents does move the file pointer, so we do that here
        if($maxsize > 0)
            fseek($this->stream, $maxsize + $offset, SEEK_SET);

        return $contents;
    }

    /**
     * Reads a line of text from the file
     *
     * @param int $maxsize The maximum amount of bytes to read. To read all data, set to -1
     * @param string $delim The end of line delimiter. Do not set unless your having problems
     *	 with detecting the end lines, or want to set a custom line break.
     * @return string Returns the current line in the file
     */
    public function readLine($maxsize = -1, $delim = null)
    {
        // If we have no line delimiter, use fgets
        if($delim === null)
            return fgets($this->stream, $maxsize);

        $result = "";
        $count = $maxsize;
        while( !feof( $this->stream ) )
        {
            // Break on max size
            if($maxsize > 0 && $count == 0)
                break;

            // Read next character
            $tmp = fgetc( $this->stream );

            // If character is the delim, break
            if( $tmp == $delim )
                return $result;
            else
                $result .= $tmp;

            --$count;
        }
        return (!empty($result)) ? $result : false;
    }

    /**
     * Reads character from the file
     *
     * @return string
     */
    public function readChar()
    {
        return fgetc( $this->stream );
    }

    /**
     * Writes to the file stream
     *
     * @param string $stringData The string to write to the file
     * @return int Returns the number of bytes that were written
     */
    public function write($stringData)
    {
        return fwrite($this->stream, $stringData);
    }

    /**
     * Truncates the file to the specified size
     *
     * @param int $size The size to truncate to.
     * @return bool
     */
    public function truncate($size = 0)
    {
        return ftruncate($this->stream, $size);
    }

    /**
     * Sets the file position indicator for the file
     *
     * @param int $position The offset, measured in bytes from the beginning of the file
     * @param int $whence The seek constant type (SEEK_SET, SEEK_ CUR, SEEK_END)
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @return bool Returns whether the seek was successful
     */
    public function seek($position, $whence = SEEK_SET)
    {
        return (fseek($this->stream, $position, $whence) == 0);
    }

    public function lock($exclusive = true)
    {
        return flock($this->stream, ($exclusive) ? LOCK_EX : LOCK_SH);
    }

    public function unlock()
    {
        return flock($this->stream, LOCK_UN);
    }

    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Closes the filestream
     *
     * @return void
     */
    public function close()
    {
        $this->read();
        fclose($this->stream);
    }
}