<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Traits;


/**
 * HasFileUsage
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @version : 1.0
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Traits
 */
trait HasFileUsage
{
    /**
     * Get the path for the given file.
     *
     * @param string $file
     * @param string $path
     *
     * @return string
     */
    protected function getFilePath(string $file, string $path)
    {
        return join(DIRECTORY_SEPARATOR, [$path, $file]);
    }

    /**
     * Assert the file extension of the given file name to be of the given extension.
     *
     * @param string $fileName
     * @param string $extension
     *
     * @return string
     */
    protected function assertFileExtension(string $fileName, string $extension)
    {
        if(ends_with($fileName, $extension)) {
            return $fileName;
        }

        if(!starts_with($extension, ".")) {
            $extension = ".$extension";
        }

        return "$fileName$extension";
    }

    /**
     * Remove the given file names from the given path.
     *
     * @param array  $fileNames
     * @param string $path
     */
    protected function removeFiles(array $fileNames, string $path)
    {
        foreach($fileNames AS $fileName) {
            $filePath = $this->getFilePath($fileName, $path);
            if(file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Remove the given file names from the temp directory.
     *
     * @param array $fileNames
     */
    protected function removeTempFiles(array $fileNames)
    {
        $this->removeFiles($fileNames, sys_get_temp_dir());
    }
}