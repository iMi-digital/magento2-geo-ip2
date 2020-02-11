<?php

namespace Tobai\GeoIp2\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

class TemporaryFiles
{
    const BASE_DIR = 'tobai-geoip/';

    /**
     * @var WriteInterface
     */
    private $tmpDirectory;

    /**
     * TemporaryFiles constructor.
     *
     * @param Filesystem $filesystem
     *
     * @throws FileSystemException
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->tmpDirectory = $filesystem->getDirectoryWrite(DirectoryList::TMP);
        $this->initTmpDirectory();
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    private function initTmpDirectory()
    {
        if (!$this->tmpDirectory->isExist(self::BASE_DIR)) {
            $this->tmpDirectory->create(self::BASE_DIR);
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getTmpTarPath($path)
    {
        $tarFilename = preg_replace('/\.gz$/', '', basename($path));
        $tarPath = $this->tmpDirectory->getAbsolutePath(self::BASE_DIR . DIRECTORY_SEPARATOR . $tarFilename);

        return $tarPath;
    }

    /**
     * @param string $path
     *
     * @return bool
     * @throws FileSystemException
     */
    public function delete($path)
    {
        return $this->tmpDirectory->delete($path);
    }

    /**
     * @param string $directory
     *
     * @return string
     */
    public function findDirectory($directory)
    {
        foreach (scandir($this->getTmpBasePath()) as $dir) {
            if (strpos($dir, $directory) !== false) {
                return $this->tmpDirectory->getAbsolutePath($this->getTmpBasePath() . $dir);
            }
        }

        return '';
    }

    /**
     * @return string
     */
    public function getTmpBasePath()
    {
        return $this->tmpDirectory->getAbsolutePath(self::BASE_DIR);
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return bool
     * @throws FileSystemException
     */
    public function move($source, $destination)
    {
        return $this->tmpDirectory->renameFile($source, $destination);
    }
}