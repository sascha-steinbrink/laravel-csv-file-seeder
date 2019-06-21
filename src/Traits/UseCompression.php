<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Traits;

/**
 * Use compression.
 *
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 15.05.2019
 * @version : 1.0
 */
trait UseCompression
{
    /**
     * Whether or not the exported csv files should be saved in a zip archive.
     *
     * @var bool
     */
    protected $zipped = false;

    /**
     * The archive name to use for saving.
     *
     * @var string
     */
    protected $archiveName = '';

    /**
     * The archive path.
     *
     * @var string
     */
    protected $archivePath = '';

    /**
     * Whether or not the exported zip archive should be encrypted.
     *
     * @var bool
     */
    protected $encrypted = false;

    /**
     * The password to use when encryption is enabled.
     *
     * @var string
     */
    protected $encryptionPassword = '';

    /**
     * @return bool
     */
    public function isZipped(): bool
    {
        return $this->zipped;
    }

    /**
     * @param bool $zipped
     */
    public function setZipped(bool $zipped): void
    {
        $this->zipped = $zipped;
    }

    /**
     * @return string
     */
    public function getArchiveName(): string
    {
        return $this->archiveName;
    }

    /**
     * @param string $archiveName
     */
    public function setArchiveName(string $archiveName): void
    {
        $this->archiveName = $archiveName;
    }

    /**
     * @return string
     */
    public function getArchivePath(): string
    {
        return $this->archivePath;
    }

    /**
     * @param string $archivePath
     */
    public function setArchivePath(string $archivePath): void
    {
        $this->archivePath = $archivePath;
    }

    /**
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    /**
     * @param bool $encrypted
     */
    public function setEncrypted(bool $encrypted): void
    {
        $this->encrypted = $encrypted;
    }

    /**
     * @return string
     */
    public function getEncryptionPassword(): string
    {
        return $this->encryptionPassword;
    }

    /**
     * @param string $encryptionPassword
     */
    public function setEncryptionPassword(string $encryptionPassword): void
    {
        $this->encryptionPassword = $encryptionPassword;
    }
}
