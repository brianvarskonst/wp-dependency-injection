<?php

declare(strict_types=1);

namespace Brianvarskonst\WordPress\DependencyInjection\Loader;

use Brianvarskonst\WordPress\DependencyInjection\Bundle\BundleInterface;

final class BundlesConfigLoader
{
    private string $projectRoot;

    public function __construct(
        string $projectRoot,
        private string $environment
    ) {

        $this->projectRoot = rtrim($projectRoot, '/');
    }

    public function loadBundles(): array
    {
        $configFile = $this->projectRoot . '/config/bundles.php';

        if (!file_exists($configFile)) {
            return [];
        }

        $bundlesConfig = require $configFile;

        if (!is_array($bundlesConfig)) {
            return [];
        }

        return $this->filterBundlesByEnvironment($bundlesConfig);
    }

    private function filterBundlesByEnvironment(array $bundlesConfig): array
    {
        $activeBundles = [];

        foreach ($bundlesConfig as $bundleClass => $environments) {
            if (!$this->shouldLoadBundle($environments)) {
                continue;
            }

            if (!class_exists($bundleClass)) {
                continue;
            }

            $bundle = $this->instantiateBundle($bundleClass);

            if ($bundle === null) {
                continue;
            }

            $activeBundles[] = $bundle;
        }

        return $activeBundles;
    }

    private function shouldLoadBundle(array $environments): bool
    {
        if (isset($environments['all']) && $environments['all'] === true) {
            return true;
        }

        if (isset($environments[$this->environment]) && $environments[$this->environment] === true) {
            return true;
        }

        return false;
    }

    private function instantiateBundle(string $bundleClass): ?BundleInterface
    {
        try {
            $bundle = new $bundleClass();

            if (!$bundle instanceof BundleInterface) {
                return null;
            }

            return $bundle;
        } catch (\Throwable $e) {
            error_log(sprintf(
                'Failed to instantiate bundle %s: %s',
                $bundleClass,
                $e->getMessage()
            ));

            return null;
        }
    }
}
