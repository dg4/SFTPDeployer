<?php

namespace Vendor\App;

use Vendor\App\ScanDirRecursiveTrait;
use Vendor\App\SFTPService;

class Deployer
{
    use ScanDirRecursiveTrait;
    private $localProjectPath = __DIR__ . '/../../';
    private $remoteProjectPath = '/var/www/domain/public_html/';
    private $excludedFiles = ['_*'];
    private $sftpService;

    public function getLatelyModifiedFiles($periodHours = 24)
    {
        $result = [];
        $allFiles = $this->scanDirRecursive($this->localProjectPath, $this->excludedFiles);
        $periodSeconds = $periodHours * 3600;

        foreach ($allFiles as $file) {
            $mTime = filemtime($file);

            if ($mTime + $periodSeconds > time()) {
                $result[$file] = $mTime;
            }
        }

        arsort($result); // sort by filemtime, descending

        return $result;
    }

    public function deployFile($relativeFilePath)
    {
        $sftp = $this->sftpService->getSFTP();
        $stream = @fopen('ssh2.sftp://' . $sftp . $this->remoteProjectPath . $relativeFilePath, 'w');

        if (!$stream)
            throw new \Exception("Could not open file: $relativeFilePath");

        $contents = @file_get_contents($relativeFilePath);

        if ($contents === false)
            throw new \Exception("Could not open local file: $relativeFilePath.");

        if (@fwrite($stream, $contents) === false)
            throw new \Exception("Could not send data from file: $relativeFilePath.");

        @fclose($stream);
    }

    public function regenerateServiceWorker()
    {
        $sftp = $this->sftpService->getSFTP();
        $swPath = 'service-worker.js';
        $swContent = file_get_contents($swPath);
        $checkFiles = [
            'css/site.css',
            'js/func.js',
            'js/site.js'
        ];

        //ssh2_exec($this->sftpService->getConn(), 'php -r \'clearstatecache();\''); // ensure filemtime() will return proper value, not cached - probably unneccessary in this case

        foreach ($checkFiles as $file) {
            $fileMTime = filemtime('ssh2.sftp://' . $sftp . $this->remoteProjectPath . $file); // get remote file modify time; we cannot get it from the local file, the remote filemtime may vary and service worker must work properly on remote server, the local one is negligible
            $swContent = preg_replace("|$file\?\Kv?=?\d+|", 'v=' . $fileMTime, $swContent); // put it to the content
        }

        preg_match('/cache-v\K\d+/', $swContent, $swVer); // find the actual version in cache name
        $swContent = preg_replace('/cache-v\K\d+/', $swVer[0] + 1, $swContent); // increment the version and put it to the content
        file_put_contents($swPath, $swContent); // save the new sw content to the local sw file
        $this->deployFile($swPath); // send local file to remote server
    }

    public function setSFTPService(SFTPService $sftpService)
    {
        $this->sftpService = $sftpService;
    }
}
