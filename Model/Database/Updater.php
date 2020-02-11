<?php
/**
 * Copyright © 2015 ToBai. All rights reserved.
 */

namespace Tobai\GeoIp2\Model\Database;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Tobai\GeoIp2\Helper\TemporaryFiles;
use Tobai\GeoIp2\Model\Database;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Archive\ArchiveInterface;
use Magento\Framework\Exception\LocalizedException;
use Tobai\GeoIp2\Model\WebService\Config as WebServiceConfig;

/**
 * Update db from remote server
 */
class Updater implements UpdaterInterface
{
    /**
     * @var WriteInterface
     */
    protected $directory;

    /**
     * @var ArchiveInterface
     */
    protected $archive;

    /**
     * @var Database
     */
    protected $database;

    /**
     * Database remote url
     *
     * @var string
     */
    protected $dbLocation;

    /**
     * @var string
     */
    protected $dbArchiveExt;

    /**
     * @var WebServiceConfig
     */
    private $webServiceConfig;

    /**
     * @var TemporaryFiles
     */
    private $temporaryFiles;

    /**
     * @param string $dbLocation
     * @param Database $database
     * @param Filesystem $filesystem
     * @param ArchiveInterface $archive
     * @param WebServiceConfig $webServiceConfig
     * @param TemporaryFiles $temporaryFiles
     * @param string $dbArchiveExt
     *
     * @throws FileSystemException
     */
    public function __construct(
        $dbLocation,
        Database $database,
        Filesystem $filesystem,
        ArchiveInterface $archive,
        WebServiceConfig $webServiceConfig,
        TemporaryFiles $temporaryFiles,
        $dbArchiveExt = ''
    ) {
        $this->dbLocation = $dbLocation;
        $this->database = $database;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->archive = $archive;
        $this->dbArchiveExt = $dbArchiveExt;
        $this->webServiceConfig = $webServiceConfig;
        $this->temporaryFiles = $temporaryFiles;
    }

    /**
     * @param string $dbCode
     * @throws LocalizedException
     */
    public function update($dbCode)
    {
        $this->createBaseDir();

        if (!$this->database->getDbFileName($dbCode)) {
            throw new LocalizedException(__('Database with "%1" code is not declared.', $dbCode));
        }

        $this->downloadDb($dbCode);
        $this->unpackDb($dbCode);
        $this->deletePackDb($dbCode);
    }

    /**
     * @throws LocalizedException
     */
    protected function createBaseDir()
    {
        if (!$this->directory->create(Database::BASE_DIR)) {
            throw new LocalizedException(__('Cannot create db directory.'));
        }
    }

    /**
     * @param string $dbCode
     * @throws LocalizedException
     */
    protected function downloadDb($dbCode)
    {
        $contents = $this->loadDbContent($this->getDbUrl($dbCode));

        $this->directory->writeFile($this->getDbArchiveFilePath($dbCode), $contents);

        if (!$this->directory->isExist($this->getDbArchiveFilePath($dbCode))) {
            throw new LocalizedException(__('Cannot save db file.'));
        }
    }

    /**
     * @param string $dbUrl
     * @return string
     */
    protected function loadDbContent($dbUrl)
    {
        return stream_get_contents(fopen($dbUrl, 'r', false));
    }

    /**
     * @param string $dbCode
     * @return string
     */
    protected function getDbUrl($dbCode)
    {
        $editionId = str_replace('.mmdb', '', $this->database->getDbFileName($dbCode));
        $dbUrl = str_replace('%edition_id%', $editionId, $this->dbLocation);
        $dbUrl = str_replace('%archive%', $this->dbArchiveExt, $dbUrl);
        $dbUrl = str_replace('%license_key%', $this->webServiceConfig->getLicenseKey(), $dbUrl);

        return $dbUrl;
    }

    /**
     * @param string $dbCode
     *
     * @return string
     */
    protected function getDbArchiveFilePath($dbCode)
    {
        return $this->database->getDbPath($dbCode) . '.' . $this->dbArchiveExt;
    }

    /**
     * @param string $dbCode
     *
     * @throws LocalizedException
     */
    protected function unpackDb($dbCode)
    {
        $this->archive->unpack(
            $this->directory->getAbsolutePath($this->getDbArchiveFilePath($dbCode)),
            $this->temporaryFiles->getTmpBasePath()
        );
        $this->moveDbFromTmp($dbCode);
        $this->temporaryFiles->delete($this->getTmpDbDirectory($dbCode));

        if (!$this->directory->isExist($this->database->getDbPath($dbCode))) {
            throw new LocalizedException(__('Cannot unpack db file.'));
        }
    }

    /**
     * @param string $dbCode
     *
     * @throws FileSystemException
     */
    protected function deletePackDb($dbCode)
    {
        $this->directory->delete($this->getDbArchiveFilePath($dbCode));
    }

    /**
     * @param string $dbCode
     *
     * @return void
     * @throws FileSystemException
     */
    private function moveDbFromTmp($dbCode)
    {
        $tmpDbDirectory = $this->getTmpDbDirectory($dbCode);
        $tmpDbPath = $tmpDbDirectory . DIRECTORY_SEPARATOR . $this->database->getDbFileName($dbCode);

        $this->directory->renameFile($tmpDbPath, $this->directory->getAbsolutePath($this->database->getDbPath($dbCode)));
    }

    /**
     * @param string $dbCode
     *
     * @return string
     */
    private function getTmpDbDirectory($dbCode)
    {
        $tmpDbDirectory = $this->database->getDbFileNameWithoutExtension($dbCode);
        $tmpDbDirectory = $this->temporaryFiles->findDirectory($tmpDbDirectory);

        return $tmpDbDirectory;
}
}
