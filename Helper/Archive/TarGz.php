<?php

namespace Tobai\GeoIp2\Helper\Archive;

use Magento\Framework\Archive\ArchiveInterface;
use Magento\Framework\Archive\Gz;
use Magento\Framework\Archive\Tar;
use Magento\Framework\Exception\FileSystemException;
use Tobai\GeoIp2\Helper\TemporaryFiles;

class TarGz implements ArchiveInterface
{

    /**
     * @var Gz
     */
    private $gzArchive;

    /**
     * @var Tar
     */
    private $tarArchive;

    /**
     * @var TemporaryFiles
     */
    private $temporaryFiles;

    /**
     * TarGz constructor.
     *
     * @param Gz $gzArchive
     * @param Tar $tarArchive
     *
     * @param TemporaryFiles $temporaryFiles
     */
    public function __construct(Gz $gzArchive, Tar $tarArchive, TemporaryFiles $temporaryFiles)
    {
        $this->gzArchive = $gzArchive;
        $this->tarArchive = $tarArchive;
        $this->temporaryFiles = $temporaryFiles;
    }

    /**
     * Pack file or directory.
     *
     * @param string $source
     * @param string $destination
     *
     * @return string
     * @throws FileSystemException
     */
    public function pack($source, $destination)
    {
        $tarDestination = $this->temporaryFiles->getTmpTarPath($destination);
        $tarArchive = $this->tarArchive->pack($source, $tarDestination);
        $path = $this->gzArchive->pack($tarArchive, $destination);

        $this->temporaryFiles->delete($tarArchive);

        return $path;
    }

    /**
     * Unpack file or directory.
     *
     * @param string $source
     * @param string $destination
     *
     * @return string
     * @throws FileSystemException
     */
    public function unpack($source, $destination)
    {
        $tarDestination = $this->temporaryFiles->getTmpTarPath($source);
        $tarArchive = $this->gzArchive->unpack($source, $tarDestination);
        $unpackedPath = $this->tarArchive->unpack($tarArchive, $destination);

        $this->temporaryFiles->delete($tarArchive);

        return $unpackedPath;
    }
}