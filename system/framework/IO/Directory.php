<?php
/**
 * Plexis Content Management Asp
 *
 * @file        system/framework/IO/Directory.php
 * @copyright   2013 Plexis Dev Team
 * @license     GNU GPL v3
 */
namespace System\IO;
use DirectoryNotFoundException;
use InvalidArgumentException;
use IOException;
use SecurityException;
use System\Collections\ListObject;

/**
 * A Directory class used to preform advanced operations and provide information
 * about the directory.
 *
 * @author      Steven Wilson 
 * @package     Asp
 * @subpackage  IO
 */
class Directory
{
    /**
     * Creates a new Directory to the specified path
     *
     * @param string $sPath The directory path
     * @param int $iChmod The (octal) chmod permissions to assign this directory
     * @return bool
     */
    public static function CreateDirectory($sPath, $iChmod = 0755)
    {
        // If the directory exists, just return true
        if(is_dir($sPath))
            return true;

        // Get current directory mask
        $oldumask = umask(0);
        if( @mkdir($sPath, $iChmod, true) == false )
            return false;

        // Return to the old file mask, and return true
        umask($oldumask);
        return true;
    }

    /**
     * Deletes an empty (unless $recursive is true) directory from a specified path
     *
     * @param string $sPath The directory path
     * @param bool $bRecursive If true, all sub files and directories will be removed.
     * @return bool
     *
     *
     * @TODO Finish this function
     */
    public static function Delete($sPath, $bRecursive = false)
    {
        if(!$bRecursive)
        {
            $Dir = new DirectoryInfo($sPath);
            $Dir->delete();
        }
        else
        {

        }
    }

    /**
     * Returns whether a specified directory exists
     *
     * @param string $sPath The directory path
     * @return bool
     */
    public static function Exists($sPath)
    {
        return is_dir($sPath);
    }

    /**
     * Gets the names of subdirectories (including their paths) in the specified directory
     *
     * @param string $sPath The directory path
     * @param string $sSearchPattern If defined, the sub-dir must match the specified search
     *	 pattern in the specified directory in order to be returned in the list
     *
     * @throws DirectoryNotFoundException Thrown if the directory path doesn't exist
     * @throws SecurityException Thrown if the directory cant be opened because of permissions
     *
     * @return ListObject
     */
    public static function GetDirectories($sPath, $sSearchPattern = null)
    {
        // Make sure the directory exists
        if( !is_dir($sPath) )
            throw new DirectoryNotFoundException("Directory \"{$sPath}\" does not exist");

        // Open the directory
        $handle = @opendir($sPath);
        if($handle === false)
            throw new SecurityException('Unable to open folder "'. $sPath .'"');

        // Refresh vars
        $filelist = new ListObject();

        // Loop through each file
        while(false !== ($f = readdir($handle)))
        {
            // Skip self and parent directories
            if($f == "." || $f == "..") continue;

            // make sure we establish the full path to the file again
            $file = Path::Combine($sPath, $f);

            // If is directory, call this method again to loop and delete ALL sub dirs.
            if( is_dir($file) )
            {
                if(!empty($sSearchPattern))
                {
                    // If filename matches the regex, add to list
                    if(preg_match("/{$sSearchPattern}/i", $f))
                        $filelist[] = $file;
                }
                else
                    $filelist[] = $file;
            }
        }

        // Close our path
        closedir($handle);
        return $filelist;
    }

    /**
     * Returns the names of files (including their paths) in the specified directory.
     *
     * @param string $sPath The directory path
     * @param string $sSearchPattern If defined, the file must match the specified search
     *	 pattern in the specified directory in order to be returned in the list
     *
     * @throws DirectoryNotFoundException Thrown if the directory path doesn't exist
     * @throws SecurityException Thrown if the directory cant be opened because of permissions
     *
     * @return \System\Collections\ListObject
     */
    public static function GetFiles($sPath, $sSearchPattern = null)
    {
        // Make sure the directory exists
        if( !is_dir($sPath) )
            throw new DirectoryNotFoundException("Directory \"{$sPath}\" does not exist");

        // Open the directory
        $handle = @opendir($sPath);
        if($handle === false)
            throw new SecurityException('Unable to open folder "'. $sPath .'"');

        // Refresh vars
        $filelist = new ListObject();

        // Loop through each file
        while(false !== ($f = readdir($handle)))
        {
            // Skip self and parent directories
            if($f == "." || $f == "..") continue;

            // make sure we establish the full path to the file again
            $file = Path::Combine($sPath, $f);

            // If is directory, call this method again to loop and delete ALL sub dirs.
            if( !is_dir($file) )
            {
                if(!empty($sSearchPattern))
                {
                    // If filename matches the regex, add to list
                    if(preg_match("/{$sSearchPattern}/i", $f))
                        $filelist[] = $file;
                }
                else
                    $filelist[] = $file;
            }
        }

        // Close our path
        closedir($handle);
        return $filelist;
    }

    /**
     * Retrieves the parent directory of the specified path
     *
     * @param string $sPath The path for which to retrieve the parent directory.
     *
     * @throws DirectoryNotFoundException Thrown if the directory path doesn't exist
     * @throws SecurityException Thrown if the directory cant be opened because of permissions
     *
     * @return \System\IO\DirectoryInfo|null The parent directory, or null if path is the root directory
     */
    public static function GetParent($sPath)
    {
        $parent = dirname($sPath);
        return ($parent == DIRECTORY_SEPARATOR || $parent == ".") ? null : new DirectoryInfo($parent);
    }

    /**
     * Moves a directory and its contents to a new location
     *
     * This method will not merge two directories. If the Destination directory
     * exists, then an IOException will be thrown with an error code of 1. If you
     * require two directories be merged, then use the Directory::Merge() method.
     *
     * @param string $sSourceDirName The full file path, including filename, of the
     *		file we are moving
     * @param string $sDestDirName The full file path, including filename, of the
     *		file that will be created
     *
     * @throws \DirectoryNotFoundException if the Source directory doesn't exist
     * @throws \InvalidArgumentException Thrown if any parameters are left null
     * @throws \IOException Thrown if there was an error creating the directory,
     * 	 or opening the destination directory after it was created, or if the
     *	 destination directory already exists
     *
     * @return bool
     */
    public static function Move($sSourceDirName, $sDestDirName)
    {
        // Make sure we have a filename
        if(empty($sSourceDirName) || empty($sDestDirName))
            throw new InvalidArgumentException("Invalid file name passed");

        // Make sure Dest directory exists
        if( !is_dir($sSourceDirName) )
            throw new DirectoryNotFoundException("Source Directory \"{$sSourceDirName}\" does not exist.");

        // Make sure Dest doesn't directory exist
        if( is_dir($sDestDirName) )
            throw new IOException("Destination directory \"{$sDestDirName}\" already exists.", 1);

        // Rename the directory
        return @rename($sSourceDirName, $sDestDirName);
    }

    /**
     * Merges a source directory into a destination directory
     *
     * If the Destination directory does not exist, this method will attempt to create it.
     * The source directory must exist! After the operation, only the Destination directory
     * will remain, and the source directory will be removed.
     *
     * @param string $sSourceDirName The full file path of the source directory
     * @param string $sDestDirName The full file path of the destination directory
     * @param bool $Overwrite Indicates whether files from the source directory
     *	 will overwrite files of the same name in the destination folder
     *
     * @throws InvalidArgumentException Thrown if any parameters are left null
     * @throws IOException Thrown if there was an error creating the directory,
     * 	 or opening the destination directory after it was created, or if there
     *	 an error moving over a file or directory to the destination directory
     *
     * @return void
     */
    public static function Merge($sSourceDirName, $sDestDirName, $Overwrite = true)
    {
        // Make sure we have a filename
        if(empty($sSourceDirName) || empty($sDestDirName))
            throw new InvalidArgumentException("Invalid file name passed");

        // Make sure Dest directory exists
        $Source = new DirectoryInfo($sSourceDirName);
        $Dest = new DirectoryInfo($sDestDirName, true);

        // Create source sub directories in the destination directory
        foreach($Source->getDirectories() as $Dir)
            /** @noinspection PhpUndefinedMethodInspection for $Dir->name() */
            self::Merge(
                Path::Combine($sSourceDirName, $Dir->name()),
                Path::Combine($sDestDirName, $Dir->name()),
                $Overwrite
            );

        // Copy over files
        foreach($Source->getFiles() as $File)
        {
            /** @noinspection PhpUndefinedMethodInspection for $File->name() */
            $destFileName = Path::Combine($sDestDirName, $File->name());
            if(!$Overwrite && $Dest->getFiles()->contains($destFileName))
                continue;

            /** @noinspection PhpUndefinedMethodInspection for $File->moveTo() */
            $File->moveTo( $destFileName );
        }

        // Remove the source directory
        @rmdir($sSourceDirName);
    }
}