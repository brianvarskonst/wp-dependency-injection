<?php

namespace Brianvarskonst\WordPress\DependencyInjection\Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;

interface BundleInterface
{
    public function build(ContainerBuilder $container): void;

    public function boot(): void;

    public function getName(): string;

    public function getPath(): string;
}
