<?php

declare(strict_types=1);

namespace Brianvarskonst\WordPress\DependencyInjection\Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

abstract class AbstractBundle implements BundleInterface
{
    protected string $path;

    public function __construct()
    {
        $this->path = dirname((new \ReflectionClass($this))->getFileName());
    }

    public function build(ContainerBuilder $container): void
    {
        $extension = $this->getContainerExtension();

        if ($extension === null) {
            return;
        }

        $container->registerExtension($extension);
    }

    public function boot(): void
    {
    }

    public function getName(): string
    {
        $className = get_class($this);
        $bundleName = substr($className, strrpos($className, '\\') + 1);

        if (str_ends_with($bundleName, 'Bundle')) {
            return substr($bundleName, 0, -6);
        }

        return $bundleName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return null;
    }

    public function getNamespace(): string
    {
        return (new \ReflectionClass($this))->getNamespaceName();
    }
}
