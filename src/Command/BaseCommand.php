<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Command;

use DanielPieper\MergeReminder\Config\AppConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Yaml\Yaml;

class BaseCommand extends Command
{
    /**
     * @param string|null $currentDir
     * @return string
     */
    protected function getConfigurationFile($currentDir = null): string
    {
        if ($currentDir === null) {
            $currentDir = realpath(__DIR__ . '/../../');
        }
        $configDirectories = [
            implode(DIRECTORY_SEPARATOR, [$this->getUserHomeFolder(), '.config', 'mrcli']),
        ];

        $locator = new FileLocator($configDirectories);
        $configurationFile = $locator->locate('config.yaml', $currentDir, true);

        return (string)$configurationFile;
    }

    /**
     * @param null $currentDir
     * @return array
     */
    protected function getConfiguration($currentDir = null): array
    {
        $filename = $this->getConfigurationFile($currentDir);

        // Load configuration
        $configFile = Yaml::parseFile($filename);
        return (new Processor())->processConfiguration(
            new AppConfiguration(),
            [$configFile]
        );
    }

    /**
     * @return null|string
     */
    protected function getUserHomeFolder()
    {
        $home = (string)getenv('HOME');
        if (!empty($home)) {
            $home = rtrim($home, '/');
        }
        return empty($home) ? null : $home;
    }
}
