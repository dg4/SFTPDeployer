<?php

namespace Vendor\App;

trait ScanDirRecursiveTrait
{
    function scanDirRecursive($dir, $excludedFiles = [])
    {
        $result = [];

        foreach (scandir($dir) as $filename) {
            if ($this->isExcludedFile($filename, $excludedFiles) || $filename[0] === '.') 
                continue;

            $filePath = $dir . DIRECTORY_SEPARATOR . $filename;

            if (is_dir($filePath)) {
                foreach ($this->scanDirRecursive($filePath, $excludedFiles) as $childFilename) {
                    $result[] = $filename . DIRECTORY_SEPARATOR . $childFilename;
                }
            } else {
                $result[] = $filename;
            }
        }

        return $result;
    }

    function isExcludedFile($filename, $excludedFiles)
    {
        foreach ($excludedFiles as $namePattern) {
            $pattern = '#(?:^|/)' . str_replace('*', '.*?', $namePattern) . '$#';

            if (\preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }
}
