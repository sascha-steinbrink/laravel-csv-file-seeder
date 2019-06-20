<?php

namespace SaschaSteinbrink\LaravelCsvFileSeeder\Traits;

/**
 * Has config file.
 *
 * @version : 1.0
 * @author  : Sascha Steinbrink <sascha.steinbrink@gmx.de>
 * @created : 11.05.2019
 * @package SaschaSteinbrink\LaravelCsvFileSeeder\Traits
 */
trait HasConfigFile
{
    /**
     * An configuration array parsed through the constructor.
     *
     * @var array
     */
    protected $inputConfig = [];

    /**
     * The name of the configuration file to use.
     *
     * @var string
     */
    protected $configFileName = 'laravel-csv-file-seeder';

    /**
     * @return array
     */
    public function getInputConfig(): array
    {
        return $this->inputConfig;
    }

    /**
     * @param array $inputConfig
     */
    public function setInputConfig(array $inputConfig): void
    {
        $this->inputConfig = $inputConfig;
    }

    /**
     * @return string
     */
    public function getConfigFileName(): string
    {
        return $this->configFileName;
    }

    /**
     * @param string $configFileName
     */
    public function setConfigFileName(string $configFileName): void
    {
        $this->configFileName = $configFileName;
    }

    /**
     * Read the configuration file and the input config.
     */
    protected function readConfig()
    {
    }

    /**
     * Get the configuration value for the given key. If an input config exists with the
     * given key the value of the input config will be returned. Otherwise the value from
     * the configuration file will be used with a fallback to the default value if not present.
     *
     * @param string      $configKey
     * @param             $default
     * @param string|null $prefix
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function getConfigValue(string $configKey, $default, ?string $prefix = null)
    {
        if (array_key_exists($configKey, $this->inputConfig) && $this->inputConfig[$configKey] !== null) {
            return $this->inputConfig[$configKey];
        }

        return $this->readConfigValue($configKey, $default, $prefix);
    }

    /**
     * Read the given config key from the configuration file using the default value as
     * a fallback.
     *
     * @param string      $configKey
     * @param             $default
     * @param string|null $prefix
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function readConfigValue(string $configKey, $default, ?string $prefix = null)
    {
        $prefix = $this->parseConfigPrefix($prefix);
        return config("$this->configFileName.$prefix$configKey", $default);
    }

    /**
     * Parse the given prefix.
     *
     * @param string|null $prefix
     *
     * @return string
     */
    protected function parseConfigPrefix(?string $prefix)
    {
        if(!filled($prefix))
        {
            return '';
        }

        return ends_with($prefix, '.') ? $prefix : "$prefix.";
    }
}