<?php

declare(strict_types=1);

namespace Brianvarskonst\WordPress\DependencyInjection\Bundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class BundleLoader
{
    private array $bundles = [];
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    public function registerBundle(BundleInterface $bundle): void
    {
        $bundleName = $bundle->getName();

        if (isset($this->bundles[$bundleName])) {
            return;
        }

        $this->bundles[$bundleName] = $bundle;
    }

    public function registerBundles(array $bundles): void
    {
        foreach ($bundles as $bundle) {
            if (!$bundle instanceof BundleInterface) {
                continue;
            }

            $this->registerBundle($bundle);
        }
    }

    public function loadBundles(ContainerBuilder $container): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->build($container);
            $this->loadBundleConfiguration($container, $bundle);
            $this->loadBundleServices($container, $bundle);
            $this->registerBundleParameters($container, $bundle);
        }
    }

    public function bootBundles(): void
    {
        foreach ($this->bundles as $bundle) {
            $bundle->boot();
        }
    }

    public function getBundles(): array
    {
        return $this->bundles;
    }

    private function loadBundleConfiguration(ContainerBuilder $container, BundleInterface $bundle): void
    {
        $configDir = $this->projectRoot . '/config/packages';

        if (!is_dir($configDir)) {
            return;
        }

        $bundleName = strtolower($bundle->getName());
        $this->loadConfigFile($container, $configDir, $bundleName);
    }

    private function loadConfigFile(ContainerBuilder $container, string $configDir, string $bundleName): void
    {
        $fileLocator = new FileLocator($configDir);

        $yamlFile = $configDir . '/' . $bundleName . '.yaml';
        if (file_exists($yamlFile)) {
            $loader = new YamlFileLoader($container, $fileLocator);
            $loader->load($bundleName . '.yaml');
        }

        $phpFile = $configDir . '/' . $bundleName . '.php';
        if (file_exists($phpFile)) {
            $loader = new PhpFileLoader($container, $fileLocator);
            $loader->load($bundleName . '.php');
        }
    }

    private function loadBundleServices(ContainerBuilder $container, BundleInterface $bundle): void
    {
        $bundlePath = $bundle->getPath();
        $configDir = $bundlePath . '/Resources/config';

        if (!is_dir($configDir)) {
            return;
        }

        $fileLocator = new FileLocator($configDir);

        $servicesYaml = $configDir . '/services.yaml';
        if (file_exists($servicesYaml)) {
            $loader = new YamlFileLoader($container, $fileLocator);
            $loader->load('services.yaml');
        }

        $servicesPhp = $configDir . '/services.php';
        if (file_exists($servicesPhp)) {
            $loader = new PhpFileLoader($container, $fileLocator);
            $loader->load('services.php');
        }
    }

    private function registerBundleParameters(ContainerBuilder $container, BundleInterface $bundle): void
    {
        $bundleName = $bundle->getName();
        $bundlePath = $bundle->getPath();

        $container->setParameter($bundleName . '.path', $bundlePath);
        $container->setParameter($bundleName . '.namespace', $bundle->getNamespace());
    }
}
