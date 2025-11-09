<?php

declare(strict_types=1);

namespace Brianvarskonst\WordPress\DependencyInjection;

use Brianvarskonst\WordPress\DependencyInjection\Loader\BundlesConfigLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Application
{
    public const CONTAINER_LOADED = 'symfony_container_loaded';

    private static ?Application $instance = null;

    private ContainerInterface $container;

    private function __construct()
    {
        $this->initialize();
        $this->register();
    }

    public static function boot(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }

    private function initialize(): void
    {
        $env = $this->resolveEnvironment();
        $root = $this->resolveProjectRoot();
        $base = dirname($root);

        $loader = new Kernel(
            $root,
            $this->resolveCacheDirectory($env),
            $this->isDebugMode(),
            $env
        );

        $loader->registerBundles(
            $this->discoverBundles(
                $base,
                $env
            )
        );

        $this->container = $loader->getContainer();
    }

    private function discoverBundles(string $projectRoot, string $environment): array
    {
        $configLoader = new BundlesConfigLoader($projectRoot, $environment);
        $configBundles = $configLoader->loadBundles();

        $filterBundles = [];
        $filterBundles = apply_filters('symfony_register_bundles', $filterBundles);

        if (!is_array($filterBundles)) {
            $filterBundles = [];
        }

        return array_merge($configBundles, $filterBundles);
    }
    private function register(): void
    {
        add_action('plugins_loaded', function() {
            $this->initialize();

            do_action('symfony_before_container_build');
        }, 5);

        add_action('init', function() {
            $this->initialize();

            do_action(self::CONTAINER_LOADED, $this->container);
        }, 1);
    }

    private function resolveProjectRoot(): string
    {
        return dirname(
            defined('WP_CONTENT_DIR')
                ? WP_CONTENT_DIR
                : ABSPATH . 'wp-content'
        );
    }

    private function resolveCacheDirectory(string $env): string
    {
        $cacheDir = sprintf(
            "%s/../var/cache/%s",
            $this->resolveProjectRoot(),
            $env
        );

        if (!file_exists($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
                throw new \RuntimeException(
                    sprintf('Directory "%s" was not created', $cacheDir)
                );
            }
        }

        return $cacheDir;
    }

    private function isDebugMode(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    private function resolveEnvironment(): string
    {
        $env = $_ENV['WORDPRESS_ENV'] ?? $_SERVER['WORDPRESS_ENV'] ?? null;

        if ($env) {
            return $env;
        }

        return $this->isDebugMode() ? 'dev' : 'prod';
    }
}
