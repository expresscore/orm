<?php
/**
 * This file is part of the ExpressCore package.
 *
 * (c) Marcin Stodulski <marcin.stodulski@devsprint.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace expresscore\orm;

use Exception;
use expresscore\cache\Cache;
use ReflectionClass;
use Symfony\Component\Yaml\Yaml;

class ConfigurationLoader
{
    private string $mode;
    private string $entityConfigurationDir;
    private array $classesConfigurations = [];

    public function __construct(string $entityConfigurationDir, string $mode = 'prod')
    {
        $this->entityConfigurationDir = $entityConfigurationDir;
        $this->mode = $mode;
    }

    public function loadClassConfiguration(string $className)
    {
        $reflection = new ReflectionClass($className);
        $shortClassName = $reflection->getShortName();
        $filePath = $this->entityConfigurationDir . $shortClassName . '.orm.yml';

        if (!file_exists($filePath)) {
            throw new Exception('Configuration orm file ' . $filePath . ' does not exists.');
        }

        $variableName = 'config/orm/' . $className;

        switch ($this->mode) {
            case 'dev':
                if (isset($this->classesConfigurations[$variableName])) {
                    return $this->classesConfigurations[$variableName];
                } else {
                    $entityConfiguration = $this->getClassConfiguration($reflection, $filePath);
                    $this->classesConfigurations[$variableName] = $entityConfiguration;

                    return $entityConfiguration;
                }
            case 'prod':
                if (Cache::checkIfVariableExistsInCache($variableName)) {
                    return Cache::getVariableValueFromCache($variableName);
                } else {
                    $entityConfiguration = $this->getClassConfiguration($reflection, $filePath);
                    Cache::setVariableValueInCache($variableName, $entityConfiguration, 600);

                    return $entityConfiguration;
                }
        }

        throw new Exception('Unexpected ConfigurationLoader mode: ' . $this->mode);
    }

    private function getClassConfiguration(ReflectionClass $reflection, string $filePath) : array
    {
        $entityConfiguration = Yaml::parseFile($filePath);
        $entityConfiguration['filePath'] = realpath($reflection->getFileName());
        $entityConfiguration['configFilePath'] = realpath($filePath);

        return $entityConfiguration;
    }
}