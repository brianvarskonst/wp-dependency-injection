<?php

declare(strict_types=1);

namespace Brianvarskonst\WordPress\DependencyInjection;

use Brianvarskonst\WordPress\DependencyInjection\Bundle\BundleInterface;
use Brianvarskonst\WordPress\DependencyInjection\Bundle\BundleLoader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;

class Kernel
{
    private static ?ContainerInterface $container = null;

    private string $projectRoot;

    private string $cacheDir;

    private bool $debug;

    private string $environment;

    private BundleLoader $bundleLoader;

    public function __construct(
        string $projectRoot,
        string $cacheDir,
        bool $debug = false,
        string $environment = 'prod'
    ) {

        $this->projectRoot = rtrim($projectRoot, '/');
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->debug = $debug;
        $this->environment = $environment;
        $this->bundleLoader = new BundleLoader($this->projectRoot);
    }

    public function getContainer(): ContainerInterface
    {
        if (self::$container !== null) {
            return self::$container;
        }

        $cachedContainerFile = $this->getCachedContainerPath();

        if ($this->shouldUseCachedContainer($cachedContainerFile)) {
            self::$container = $this->loadCachedContainer($cachedContainerFile);
            $this->bundleLoader->bootBundles();
            return self::$container;
        }

        self::$container = $this->buildContainer();
        $this->cacheContainer(self::$container, $cachedContainerFile);
        $this->bundleLoader->bootBundles();

        return self::$container;
    }

    public function registerBundle(BundleInterface $bundle): void
    {
        $this->bundleLoader->registerBundle($bundle);
    }

    public function registerBundles(array $bundles): void
    {
        $this->bundleLoader->registerBundles($bundles);
    }

    private function buildContainer(): ContainerInterface
    {
        $containerBuilder = new ContainerBuilder();

        $this->configureContainer($containerBuilder);
        $this->bundleLoader->loadBundles($containerBuilder);
        $this->loadConfigFiles($containerBuilder);

        $containerBuilder->compile();

        return $containerBuilder;
    }

    private function configureContainer(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->setParameter('kernel.project_dir', $this->projectRoot);
        $containerBuilder->setParameter('kernel.cache_dir', $this->cacheDir);
        $containerBuilder->setParameter('kernel.debug', $this->debug);
        $containerBuilder->setParameter('kernel.environment', $this->environment);

        $this->loadEnvironmentVariablesAsParameters($containerBuilder);

        $containerBuilder->registerForAutoconfiguration(LoggerInterface::class)
            ->addTag('monolog.logger');
    }

    private function loadConfigFiles(ContainerBuilder $containerBuilder): void
    {
        $configDir = $this->projectRoot . '/config';

        if (!is_dir($configDir)) {
            return;
        }

        $fileLocator = new FileLocator($configDir);

        $this->loadYamlConfigs($containerBuilder, $fileLocator, $configDir);
        $this->loadPhpConfigs($containerBuilder, $fileLocator, $configDir);
        $this->loadEnvironmentConfigs($containerBuilder, $fileLocator, $configDir);
    }

    private function loadYamlConfigs(ContainerBuilder $containerBuilder, FileLocator $fileLocator, string $configDir): void
    {
        $yamlLoader = new YamlFileLoader($containerBuilder, $fileLocator);
        $yamlFiles = $this->findConfigFiles($configDir, 'yaml');

        foreach ($yamlFiles as $file) {
            $yamlLoader->load($file);
        }
    }

    private function loadPhpConfigs(ContainerBuilder $containerBuilder, FileLocator $fileLocator, string $configDir): void
    {
        $phpLoader = new PhpFileLoader($containerBuilder, $fileLocator);
        $phpFiles = $this->findConfigFiles($configDir, 'php');

        foreach ($phpFiles as $file) {
            $phpLoader->load($file);
        }
    }

    private function findConfigFiles(string $configDir, string $extension): array
    {
        $pattern = $configDir . '/*.{' . $extension . '}';
        $files = glob($pattern, GLOB_BRACE);

        return $files ?: [];
    }

    private function shouldUseCachedContainer(string $cachedContainerFile): bool
    {
        if ($this->debug) {
            return false;
        }

        return file_exists($cachedContainerFile);
    }

    private function loadCachedContainer(string $cachedContainerFile): ContainerInterface
    {
        require_once $cachedContainerFile;

        $className = 'CachedContainer';
        return new $className();
    }

    private function cacheContainer(ContainerInterface $container, string $cachedContainerFile): void
    {
        if ($this->debug) {
            return;
        }

        $this->ensureCacheDirectoryExists();

        $dumper = new PhpDumper($container);
        $cachedCode = $dumper->dump(['class' => 'CachedContainer']);

        file_put_contents($cachedContainerFile, $cachedCode);
    }

    private function ensureCacheDirectoryExists(): void
    {
        if (is_dir($this->cacheDir)) {
            return;
        }

        if (!mkdir($concurrentDirectory = $this->cacheDir, 0755, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }

    private function getCachedContainerPath(): string
    {
        return $this->cacheDir . '/container_' . $this->environment . '.php';
    }

    private function loadEnvironmentConfigs(ContainerBuilder $containerBuilder, FileLocator $fileLocator, string $configDir): void
    {
        $envConfigDir = $configDir . '/packages/' . $this->environment;

        if (!is_dir($envConfigDir)) {
            return;
        }

        $envFileLocator = new FileLocator($envConfigDir);

        $yamlLoader = new YamlFileLoader($containerBuilder, $envFileLocator);
        $yamlFiles = $this->findConfigFiles($envConfigDir, 'yaml');

        foreach ($yamlFiles as $file) {
            $yamlLoader->load($file);
        }

        $phpLoader = new PhpFileLoader($containerBuilder, $envFileLocator);
        $phpFiles = $this->findConfigFiles($envConfigDir, 'php');

        foreach ($phpFiles as $file) {
            $phpLoader->load($file);
        }
    }

    private function loadEnvironmentVariablesAsParameters(ContainerBuilder $containerBuilder): void
    {
        $envVars = [...$_ENV, ...$_SERVER];
        $excludedPrefixes = ['HTTP_', 'REQUEST_', 'REDIRECT_', 'PHP_', 'DOCUMENT_', 'SCRIPT_'];
        $allowedPrefixes = ['WORDPRESS_', 'WP_', 'DB_', 'APP_'];

        foreach ($envVars as $key => $value) {
            if (!$this->shouldIncludeEnvironmentVariable($key, $excludedPrefixes, $allowedPrefixes)) {
                continue;
            }

            if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
                continue;
            }

            $sanitizedValue = $this->sanitizeParameterValue($value);
            $paramName = 'env.' . strtolower($key);
            $containerBuilder->setParameter($paramName, $sanitizedValue);
        }
    }

    private function shouldIncludeEnvironmentVariable(string $key, array $excludedPrefixes, array $allowedPrefixes): bool
    {
        foreach ($excludedPrefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return false;
            }
        }

        if (empty($allowedPrefixes)) {
            return true;
        }

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeParameterValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if ($this->containsEnvReference($value)) {
            return $value;
        }

        return str_replace('%', '%%', $value);
    }

    private function containsEnvReference(string $value): bool
    {
        return str_contains($value, '${');
    }
}

